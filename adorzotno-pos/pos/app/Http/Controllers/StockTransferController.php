<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InterBranchTransfer;
use App\Models\InventoryBatch;
use App\Models\Warehouse;
use App\Services\InterBranchTransferService;
use App\Support\DateFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    public function __construct(private InterBranchTransferService $interBranchTransferService)
    {
    }

    public function show()
    {
        return view('stock_transfer.index');
    }

    public function list(Request $request)
    {
        $currentBranchId = $this->currentBranchId();

        $transfers = InterBranchTransfer::query()
            ->with(['fromBranch', 'fromWarehouse', 'toBranch', 'toWarehouse', 'items'])
            ->when($currentBranchId, function ($query, $branchId) {
                $query->where(function ($transferQuery) use ($branchId) {
                    $transferQuery->where('from_branch_id', $branchId)
                        ->orWhere('to_branch_id', $branchId);
                });
            }, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return DataTables()->of($transfers)
            ->addColumn('from_location', fn (InterBranchTransfer $transfer) => ($transfer->fromBranch?->name ?? 'N/A') . ' / ' . ($transfer->fromWarehouse?->name ?? 'N/A'))
            ->addColumn('to_location', fn (InterBranchTransfer $transfer) => ($transfer->toBranch?->name ?? 'N/A') . ' / ' . ($transfer->toWarehouse?->name ?? 'N/A'))
            ->addColumn('status_badge', fn (InterBranchTransfer $transfer) => $this->statusBadge($transfer->status))
            ->addColumn('requested_total', fn (InterBranchTransfer $transfer) => (int) $transfer->items->sum('requested_quantity'))
            ->addColumn('approved_total', fn (InterBranchTransfer $transfer) => (int) $transfer->items->sum('approved_quantity'))
            ->addColumn('dispatched_total', fn (InterBranchTransfer $transfer) => (int) $transfer->items->sum('dispatched_quantity'))
            ->addColumn('received_total', fn (InterBranchTransfer $transfer) => (int) $transfer->items->sum('received_quantity'))
            ->editColumn('requested_at', fn (InterBranchTransfer $transfer) => DateFormatter::dateTime($transfer->requested_at ?? $transfer->created_at))
            ->addColumn('actions', function (InterBranchTransfer $transfer) {
                return '<a href="' . route('stockTransfer.details', $transfer->id) . '" class="btn btn-sm btn-primary">View</a>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $currentBranch = $this->branchContext()->currentBranch(auth()->user());
        $defaultWarehouse = $this->branchContext()->resolveDefaultWarehouseForCurrentBranch(auth()->user());
        $transfer = new InterBranchTransfer([
            'transfer_no' => $this->generateTransferNumber(),
            'from_branch_id' => $currentBranch?->id,
            'from_warehouse_id' => $defaultWarehouse?->id,
            'to_branch_id' => null,
            'to_warehouse_id' => null,
        ]);

        return view('stock_transfer.create', array_merge(
            $this->formDependencies(),
            compact('transfer')
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedRequestPayload($request);

        $transfer = DB::transaction(function () use ($payload) {
            $transfer = InterBranchTransfer::query()->create($payload['transfer']);

            foreach ($payload['items'] as $item) {
                $transfer->items()->create($item);
            }

            return $transfer;
        });

        Session::flash(
            'success',
            (int) $transfer->from_branch_id === (int) $transfer->to_branch_id
                ? 'Warehouse transfer request created successfully.'
                : 'Branch transfer request created successfully.'
        );

        return redirect()->route('stockTransfer.details', $transfer->id);
    }

    public function details(int $id)
    {
        $transfer = $this->scopedTransferQuery()
            ->with([
                'items.batch.sku.product',
                'fromBranch',
                'fromWarehouse',
                'toBranch',
                'toWarehouse',
                'requester',
                'approver',
                'receiver',
            ])
            ->findOrFail($id);

        return view('stock_transfer.details', [
            'transfer' => $transfer,
            'canApprove' => $this->canApprove($transfer),
            'canDispatch' => $this->canDispatch($transfer),
            'canReceive' => $this->canReceive($transfer),
            'canCancel' => $this->canCancel($transfer),
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $transfer = $this->scopedTransferQuery()->with('items')->findOrFail($id);

        if (!$this->canApprove($transfer)) {
            abort(403, 'You are not allowed to approve this transfer.');
        }

        if ($transfer->status !== InterBranchTransfer::STATUS_REQUESTED) {
            throw ValidationException::withMessages([
                'transfer' => 'Only requested transfers can be approved.',
            ]);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.approved_quantity' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($transfer, $validated) {
            $itemMap = collect($validated['items'])->keyBy(fn (array $item) => (int) $item['item_id']);
            $hasApprovedQuantity = false;

            foreach ($transfer->items as $item) {
                $row = $itemMap->get((int) $item->id);
                $approvedQuantity = (int) ($row['approved_quantity'] ?? 0);

                if ($approvedQuantity > (int) $item->requested_quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Approved quantity cannot exceed requested quantity.',
                    ]);
                }

                $item->approved_quantity = $approvedQuantity;
                $item->save();

                if ($approvedQuantity > 0) {
                    $hasApprovedQuantity = true;
                }
            }

            if (!$hasApprovedQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'Approve at least one quantity greater than zero.',
                ]);
            }

            $transfer->status = InterBranchTransfer::STATUS_APPROVED;
            $transfer->approved_by = auth()->id();
            $transfer->approved_at = now();
            $transfer->save();
        });

        return redirect()->route('stockTransfer.details', $transfer->id)
            ->with('success', 'Transfer approved successfully.');
    }

    public function dispatch(Request $request, int $id): RedirectResponse
    {
        $transfer = $this->scopedTransferQuery()->findOrFail($id);

        if (!$this->canDispatch($transfer)) {
            abort(403, 'You are not allowed to dispatch this transfer.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.dispatched_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->interBranchTransferService->dispatch($transfer, $validated['items'], auth()->id());

        return redirect()->route('stockTransfer.details', $transfer->id)
            ->with('success', 'Transfer dispatched successfully.');
    }

    public function receive(Request $request, int $id): RedirectResponse
    {
        $transfer = $this->scopedTransferQuery()->findOrFail($id);

        if (!$this->canReceive($transfer)) {
            abort(403, 'You are not allowed to receive this transfer.');
        }

        $validated = $request->validate([
            'discrepancy_note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->interBranchTransferService->receive(
            $transfer,
            $validated['items'],
            $validated['discrepancy_note'] ?? null,
            auth()->id()
        );

        return redirect()->route('stockTransfer.details', $transfer->id)
            ->with('success', 'Transfer received successfully.');
    }

    public function cancel(int $id): RedirectResponse
    {
        $transfer = $this->scopedTransferQuery()->findOrFail($id);

        if (!$this->canCancel($transfer)) {
            abort(403, 'You are not allowed to cancel this transfer.');
        }

        if (!in_array($transfer->status, [InterBranchTransfer::STATUS_REQUESTED, InterBranchTransfer::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages([
                'transfer' => 'Only requested or approved transfers can be cancelled.',
            ]);
        }

        $transfer->status = InterBranchTransfer::STATUS_CANCELLED;
        $transfer->save();

        return redirect()->route('stockTransfer.details', $transfer->id)
            ->with('success', 'Transfer cancelled successfully.');
    }

    private function formDependencies(): array
    {
        $currentBranch = $this->branchContext()->currentBranch(auth()->user());
        $fromBranches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect([$currentBranch])->filter();

        return [
            'fromBranches' => $fromBranches,
            'toBranches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->with('branch')
                ->orderBy('name')
                ->get(),
            'batches' => InventoryBatch::query()
                ->with(['sku.product', 'warehouse.branch'])
                ->where('available_quantity', '>', 0)
                ->whereHas('warehouse', function ($warehouseQuery) use ($fromBranches) {
                    $warehouseQuery->whereIn('branch_id', $fromBranches->pluck('id')->all());
                })
                ->orderBy('batch_no')
                ->get(),
        ];
    }

    private function validatedRequestPayload(Request $request): array
    {
        $items = collect($request->input('items', []))
            ->filter(fn (array $item) => !empty($item['batch_id']) || !empty($item['requested_quantity']))
            ->values()
            ->all();

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'transfer_no' => ['required', 'string', 'max:100', Rule::unique('inter_branch_transfers', 'transfer_no')],
            'from_branch_id' => ['required', 'exists:branches,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_branch_id' => ['required', 'exists:branches,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.batch_id' => ['required', 'exists:inventory_batches,id'],
            'items.*.requested_quantity' => ['required', 'integer', 'min:1'],
        ]);

        $fromBranchId = (int) $validated['from_branch_id'];
        $fromWarehouseId = (int) $validated['from_warehouse_id'];
        $toBranchId = (int) $validated['to_branch_id'];
        $toWarehouseId = (int) $validated['to_warehouse_id'];

        if (!$this->branchContext()->hasBranchAccess(auth()->user(), $fromBranchId)) {
            throw ValidationException::withMessages([
                'from_branch_id' => 'You do not have access to the source branch.',
            ]);
        }

        $fromWarehouse = Warehouse::query()->find($fromWarehouseId);
        $toWarehouse = Warehouse::query()->find($toWarehouseId);

        if ($fromWarehouse === null || (int) $fromWarehouse->branch_id !== $fromBranchId) {
            throw ValidationException::withMessages([
                'from_warehouse_id' => 'Selected source warehouse does not belong to the source branch.',
            ]);
        }

        if ($toWarehouse === null || (int) $toWarehouse->branch_id !== $toBranchId) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Selected destination warehouse does not belong to the destination branch.',
            ]);
        }

        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Destination warehouse must be different from the source warehouse.',
            ]);
        }

        $batchIds = collect($validated['items'])->pluck('batch_id');
        if ($batchIds->count() !== $batchIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Do not repeat the same source batch in multiple rows.',
            ]);
        }

        $preparedItems = collect($validated['items'])->map(function (array $item, int $index) use ($fromWarehouseId) {
            $batch = InventoryBatch::query()->findOrFail((int) $item['batch_id']);

            if ((int) $batch->warehouse_id !== $fromWarehouseId) {
                throw ValidationException::withMessages([
                    "items.$index.batch_id" => 'Selected batch does not belong to the source warehouse.',
                ]);
            }

            return [
                'sku_id' => (int) $batch->sku_id,
                'batch_id' => (int) $batch->id,
                'requested_quantity' => (int) $item['requested_quantity'],
                'approved_quantity' => 0,
                'dispatched_quantity' => 0,
                'received_quantity' => 0,
            ];
        })->all();

        return [
            'transfer' => [
                'transfer_no' => $validated['transfer_no'],
                'from_branch_id' => $fromBranchId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_branch_id' => $toBranchId,
                'to_warehouse_id' => $toWarehouseId,
                'requested_by' => auth()->id(),
                'status' => InterBranchTransfer::STATUS_REQUESTED,
                'requested_at' => now(),
                'created_at' => now(),
            ],
            'items' => $preparedItems,
        ];
    }

    private function scopedTransferQuery()
    {
        $currentBranchId = $this->currentBranchId();

        return InterBranchTransfer::query()
            ->when($currentBranchId, function ($query, $branchId) {
                $query->where(function ($transferQuery) use ($branchId) {
                    $transferQuery->where('from_branch_id', $branchId)
                        ->orWhere('to_branch_id', $branchId);
                });
            }, fn ($query) => $query->whereRaw('1 = 0'));
    }

    private function canApprove(InterBranchTransfer $transfer): bool
    {
        return $this->branchContext()->hasCrossBranchAccess(auth()->user())
            || (int) $transfer->to_branch_id === (int) $this->currentBranchId();
    }

    private function canDispatch(InterBranchTransfer $transfer): bool
    {
        return $this->branchContext()->hasCrossBranchAccess(auth()->user())
            || (int) $transfer->from_branch_id === (int) $this->currentBranchId();
    }

    private function canReceive(InterBranchTransfer $transfer): bool
    {
        return $this->branchContext()->hasCrossBranchAccess(auth()->user())
            || (int) $transfer->to_branch_id === (int) $this->currentBranchId();
    }

    private function canCancel(InterBranchTransfer $transfer): bool
    {
        return $this->branchContext()->hasCrossBranchAccess(auth()->user())
            || in_array((int) $this->currentBranchId(), [(int) $transfer->from_branch_id, (int) $transfer->to_branch_id], true);
    }

    private function generateTransferNumber(): string
    {
        $prefix = 'IBT-' . now()->format('Ymd') . '-';
        $nextId = (int) InterBranchTransfer::query()->max('id') + 1;

        return $prefix . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            InterBranchTransfer::STATUS_REQUESTED => '<label class="btn btn-secondary">Requested</label>',
            InterBranchTransfer::STATUS_APPROVED => '<label class="btn btn-primary">Approved</label>',
            InterBranchTransfer::STATUS_DISPATCHED => '<label class="btn btn-warning">Dispatched</label>',
            InterBranchTransfer::STATUS_RECEIVED => '<label class="btn btn-success">Received</label>',
            InterBranchTransfer::STATUS_CANCELLED => '<label class="btn btn-danger">Cancelled</label>',
            default => '<label class="btn btn-light">' . e($status) . '</label>',
        };
    }
}
