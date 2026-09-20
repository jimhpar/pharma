<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\InventoryBatch;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\DateFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentController extends Controller
{
    public function show()
    {
        return view('inventory_adjustment.index');
    }

    public function list()
    {
        $adjustments = InventoryAdjustment::query()
            ->with(['branch', 'warehouse', 'items'])
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id');

        return DataTables()->of($adjustments)
            ->addColumn('document_type', fn (InventoryAdjustment $adjustment) => $adjustment->document_type_label)
            ->addColumn('branch', fn (InventoryAdjustment $adjustment) => $adjustment->branch?->name ?? 'N/A')
            ->addColumn('warehouse', fn (InventoryAdjustment $adjustment) => $adjustment->warehouse?->name ?? 'N/A')
            ->addColumn('item_count', fn (InventoryAdjustment $adjustment) => $adjustment->items->count())
            ->addColumn('total_quantity', fn (InventoryAdjustment $adjustment) => $adjustment->items->sum('quantity'))
            ->editColumn('adjustment_date', fn (InventoryAdjustment $adjustment) => DateFormatter::date($adjustment->adjustment_date))
            ->setRowAttr([
                'align' => 'center',
            ])
            ->make(true);
    }

    public function create()
    {
        return $this->createDocument(InventoryAdjustment::TYPE_ADJUSTMENT);
    }

    public function createOpeningStock()
    {
        return $this->createDocument(InventoryAdjustment::TYPE_OPENING_STOCK);
    }

    public function createStockIssue()
    {
        return $this->createDocument(InventoryAdjustment::TYPE_STOCK_ISSUE);
    }

    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $inventoryService->createAdjustment($payload['adjustment'], $payload['items']);

        Session::flash('success', $this->successMessageForDocumentType($payload['adjustment']['document_type']));

        return redirect()->route('inventoryAdjustment.show');
    }

    private function formDependencies(): array
    {
        return [
            'branches' => $this->branchContext()->accessibleBranches(auth()->user()),
            'warehouses' => $this->branchContext()->accessibleWarehouses(auth()->user()),
            'skus' => Sku::query()
                ->with('product')
                ->where('status', 'active')
                ->where('track_stock', true)
                ->orderBy('sku_code')
                ->get(),
            'batches' => InventoryBatch::query()
                ->with(['sku.product', 'warehouse', 'supplier'])
                ->whereHas('warehouse', function ($warehouseQuery) {
                    $warehouseQuery->whereIn('branch_id', $this->branchContext()->accessibleBranchIds(auth()->user()));
                })
                ->orderBy('batch_no')
                ->get(),
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $items = collect($request->input('items', []))
            ->filter(fn (array $item) => !empty($item['batch_id']) || !empty($item['sku_id']) || !empty($item['quantity']))
            ->values()
            ->all();

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'document_type' => ['required', Rule::in(InventoryAdjustment::documentTypes())],
            'adjustment_no' => ['required', 'string', 'max:100', Rule::unique('inventory_adjustments', 'adjustment_no')],
            'adjustment_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'nullable|exists:inventory_batches,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.adjustment_type' => 'nullable|in:increase,decrease',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.batch_no' => 'nullable|string|max:100',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        $this->validateBranchWarehouseAccess((int) $validated['branch_id'], (int) $validated['warehouse_id']);

        $documentType = $validated['document_type'];
        $pairs = collect($validated['items'])->map(function (array $item) use ($documentType) {
            $direction = $this->normalizeAdjustmentDirection($documentType, $item['adjustment_type'] ?? null);
            $identifier = !empty($item['batch_id'])
                ? 'batch:' . $item['batch_id']
                : 'sku:' . ($item['sku_id'] ?? '') . ':batch:' . trim((string) ($item['batch_no'] ?? ''));

            return $identifier . ':' . $direction;
        });

        if ($pairs->count() !== $pairs->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Do not repeat the same batch or SKU/batch combination in multiple rows.',
            ]);
        }

        $preparedItems = collect($validated['items'])->map(function (array $item, int $index) use ($documentType) {
            $direction = $this->normalizeAdjustmentDirection($documentType, $item['adjustment_type'] ?? null);
            $hasBatch = !empty($item['batch_id']);
            $hasSku = !empty($item['sku_id']);

            if ($direction === 'increase' && !$hasBatch && !$hasSku) {
                throw ValidationException::withMessages([
                    "items.$index.sku_id" => 'Select a SKU when no existing batch is chosen.',
                ]);
            }

            if ($direction === 'decrease' && !$hasBatch && !$hasSku) {
                throw ValidationException::withMessages([
                    "items.$index.batch_id" => 'Select a batch or SKU for outbound stock.',
                ]);
            }

            return [
                'batch_id' => $hasBatch ? (int) $item['batch_id'] : null,
                'sku_id' => $hasSku ? (int) $item['sku_id'] : null,
                'adjustment_type' => $direction,
                'quantity' => (int) $item['quantity'],
                'batch_no' => $item['batch_no'] ?? null,
                'unit_cost' => isset($item['unit_cost']) ? (float) $item['unit_cost'] : null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'reason' => $item['reason'] ?? null,
            ];
        })->all();

        return [
            'adjustment' => [
                'document_type' => $documentType,
                'adjustment_no' => $validated['adjustment_no'],
                'adjustment_date' => $validated['adjustment_date'],
                'branch_id' => (int) $validated['branch_id'],
                'warehouse_id' => (int) $validated['warehouse_id'],
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ],
            'items' => $preparedItems,
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

    private function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ-' . now()->format('Ymd') . '-';
        $nextId = (int) InventoryAdjustment::query()->max('id') + 1;

        return $prefix . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    private function createDocument(string $documentType)
    {
        $defaultWarehouse = $this->branchContext()->resolveDefaultWarehouseForCurrentBranch(auth()->user());
        $adjustment = new InventoryAdjustment([
            'document_type' => $documentType,
            'adjustment_no' => $this->generateDocumentNumber($documentType),
            'adjustment_date' => now()->toDateString(),
            'branch_id' => $this->currentBranchId(),
            'warehouse_id' => $defaultWarehouse?->id,
            'note' => '',
        ]);

        return view('inventory_adjustment.create', array_merge(
            $this->formDependencies(),
            [
                'adjustment' => $adjustment,
                'documentType' => $documentType,
                'pageTitle' => $this->pageTitleForDocumentType($documentType),
                'cardTitle' => $this->cardTitleForDocumentType($documentType),
                'submitLabel' => $this->submitLabelForDocumentType($documentType),
            ]
        ));
    }

    private function normalizeAdjustmentDirection(string $documentType, ?string $direction): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'increase',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'decrease',
            default => $direction === 'decrease' ? 'decrease' : 'increase',
        };
    }

    private function generateDocumentNumber(string $documentType): string
    {
        $prefix = match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'OPN',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'ISS',
            default => 'ADJ',
        };

        $nextId = (int) InventoryAdjustment::query()->max('id') + 1;

        return $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    private function pageTitleForDocumentType(string $documentType): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'Opening Stock Entry',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'Stock Issue Create',
            default => 'Stock Adjustment Create',
        };
    }

    private function cardTitleForDocumentType(string $documentType): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'Opening Stock Form',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'Stock Issue Form',
            default => 'Adjustment Form',
        };
    }

    private function submitLabelForDocumentType(string $documentType): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'Save Opening Stock',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'Save Stock Issue',
            default => 'Save Adjustment',
        };
    }

    private function successMessageForDocumentType(string $documentType): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'Opening stock saved successfully.',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'Stock issue saved successfully.',
            default => 'Stock adjustment saved successfully.',
        };
    }
}
