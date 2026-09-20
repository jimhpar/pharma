<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\DateFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierReturnController extends Controller
{
    public function show()
    {
        return view('supplier_return.index');
    }

    public function list()
    {
        $returns = SupplierReturn::query()
            ->with(['branch', 'warehouse', 'supplier', 'items'])
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        return DataTables()->of($returns)
            ->addColumn('branch', fn (SupplierReturn $return) => $return->branch?->name ?? 'N/A')
            ->addColumn('warehouse', fn (SupplierReturn $return) => $return->warehouse?->name ?? 'N/A')
            ->addColumn('supplier', fn (SupplierReturn $return) => $return->supplier?->name ?? 'N/A')
            ->addColumn('item_count', fn (SupplierReturn $return) => $return->items->count())
            ->addColumn('total_quantity', fn (SupplierReturn $return) => $return->items->sum('quantity'))
            ->editColumn('return_date', fn (SupplierReturn $return) => DateFormatter::date($return->return_date))
            ->setRowAttr([
                'align' => 'center',
            ])
            ->make(true);
    }

    public function create()
    {
        $defaultWarehouse = $this->branchContext()->resolveDefaultWarehouseForCurrentBranch(auth()->user());
        $supplierReturn = new SupplierReturn([
            'return_no' => $this->generateReturnNumber(),
            'return_date' => now()->toDateString(),
            'branch_id' => $this->currentBranchId(),
            'warehouse_id' => $defaultWarehouse?->id,
            'note' => '',
        ]);

        return view('supplier_return.create', array_merge(
            $this->formDependencies(),
            compact('supplierReturn')
        ));
    }

    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $inventoryService->createSupplierReturn($payload['supplier_return'], $payload['items']);

        Session::flash('success', 'Supplier return saved successfully.');

        return redirect()->route('supplierReturn.show');
    }

    private function formDependencies(): array
    {
        return [
            'branches' => $this->branchContext()->accessibleBranches(auth()->user()),
            'warehouses' => $this->branchContext()->accessibleWarehouses(auth()->user()),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'batches' => InventoryBatch::query()
                ->with(['sku.product', 'warehouse', 'supplier'])
                ->when($this->currentBranchId(), function ($query, $branchId) {
                    $query->whereHas('warehouse', fn ($warehouseQuery) => $warehouseQuery->where('branch_id', $branchId));
                })
                ->where('available_quantity', '>', 0)
                ->whereNotNull('supplier_id')
                ->orderBy('batch_no')
                ->get(),
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $items = collect($request->input('items', []))
            ->filter(fn (array $item) => !empty($item['batch_id']) || !empty($item['quantity']))
            ->values()
            ->all();

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'return_no' => ['required', 'string', 'max:100', Rule::unique('supplier_returns', 'return_no')],
            'return_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        $this->validateBranchWarehouseAccess((int) $validated['branch_id'], (int) $validated['warehouse_id']);

        $batchIds = collect($validated['items'])->pluck('batch_id');
        if ($batchIds->count() !== $batchIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Do not repeat the same batch in multiple rows.',
            ]);
        }

        return [
            'supplier_return' => [
                'return_no' => $validated['return_no'],
                'return_date' => $validated['return_date'],
                'branch_id' => (int) $validated['branch_id'],
                'warehouse_id' => (int) $validated['warehouse_id'],
                'supplier_id' => (int) $validated['supplier_id'],
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ],
            'items' => collect($validated['items'])->map(function (array $item) {
                return [
                    'batch_id' => (int) $item['batch_id'],
                    'quantity' => (int) $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                ];
            })->all(),
        ];
    }

    private function validateBranchWarehouseAccess(int $branchId, int $warehouseId): void
    {
        if (!$this->branchContext()->hasBranchAccess(auth()->user(), $branchId)) {
            throw ValidationException::withMessages([
                'branch_id' => 'You do not have access to the selected branch.',
            ]);
        }

        $warehouse = Warehouse::query()->find($warehouseId);

        if ($warehouse === null || (int) $warehouse->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Selected warehouse does not belong to the selected branch.',
            ]);
        }
    }

    private function generateReturnNumber(): string
    {
        $prefix = 'SRT-' . now()->format('Ymd') . '-';
        $nextId = (int) SupplierReturn::query()->max('id') + 1;

        return $prefix . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
