<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Sku;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function show()
    {
        return view('purchase_order.index');
    }

    public function list()
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['branch', 'warehouse', 'supplier'])
            ->withCount('items')
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        return DataTables()->of($purchaseOrders)
            ->addColumn('branch', function (PurchaseOrder $purchaseOrder) {
                return $purchaseOrder->branch?->name ?? 'N/A';
            })
            ->addColumn('warehouse', function (PurchaseOrder $purchaseOrder) {
                return $purchaseOrder->warehouse?->name ?? 'N/A';
            })
            ->addColumn('supplier', function (PurchaseOrder $purchaseOrder) {
                return $purchaseOrder->supplier?->name ?? 'N/A';
            })
            ->editColumn('purchase_date', function (PurchaseOrder $purchaseOrder) {
                return DateFormatter::date($purchaseOrder->purchase_date);
            })
            ->addColumn('status_badge', function (PurchaseOrder $purchaseOrder) {
                return $this->statusBadge($purchaseOrder->status);
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        $defaultWarehouse = $this->branchContext()->resolveDefaultWarehouseForCurrentBranch(auth()->user());
        $purchaseOrder = new PurchaseOrder([
            'purchase_no' => $this->generatePurchaseNumber(),
            'purchase_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_ORDERED,
            'branch_id' => $this->currentBranchId(),
            'warehouse_id' => $defaultWarehouse?->id,
            'other_charge_total' => 0,
            'paid_total' => 0,
            'note' => '',
        ]);

        return view('purchase_order.create', array_merge(
            $this->formDependencies(),
            compact('purchaseOrder')
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        $purchaseOrder = DB::transaction(function () use ($payload) {
            $purchaseOrder = PurchaseOrder::query()->create($payload['purchase_order']);

            foreach ($payload['items'] as $item) {
                $purchaseOrder->items()->create($item);
            }

            SupplierLedger::syncPurchaseOrderEntry($purchaseOrder->fresh());

            return $purchaseOrder;
        });

        Session::flash('success', 'Purchase order created successfully.');

        return redirect()->route('purchaseOrder.edit', $purchaseOrder->id);
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with(['items.sku.product', 'branch', 'warehouse', 'supplier'])
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($id);

        return view('purchase_order.edit', array_merge(
            $this->formDependencies($purchaseOrder),
            compact('purchaseOrder')
        ));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $purchaseOrder = PurchaseOrder::query()->with('items')->findOrFail($id);
        $previousSupplierId = (int) $purchaseOrder->supplier_id;

        if ($purchaseOrder->items->sum('received_quantity') > 0) {
            throw ValidationException::withMessages([
                'purchase_order' => 'Purchase orders with received stock can no longer be edited.',
            ]);
        }

        $payload = $this->validatedPayload($request, $purchaseOrder->id);

        DB::transaction(function () use ($purchaseOrder, $payload, $previousSupplierId) {
            SupplierLedger::removePurchaseOrderEntries($purchaseOrder);
            $purchaseOrder->update($payload['purchase_order']);
            $purchaseOrder->items()->delete();

            foreach ($payload['items'] as $item) {
                $purchaseOrder->items()->create($item);
            }

            $purchaseOrder->refresh();
            SupplierLedger::syncPurchaseOrderEntry($purchaseOrder);

            if ($previousSupplierId !== (int) $purchaseOrder->supplier_id) {
                SupplierLedger::rebuildBalancesForSupplier($previousSupplierId);
            }
        });

        Session::flash('success', 'Purchase order updated successfully.');

        return redirect()->route('purchaseOrder.edit', $purchaseOrder->id);
    }

    public function receive($id)
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with(['items.sku.product', 'branch', 'warehouse', 'supplier'])
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($id);

        return view('purchase_order.receive', compact('purchaseOrder'));
    }

    public function receiveStore(Request $request, $id, InventoryService $inventoryService): RedirectResponse
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with(['items.sku.product'])
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($id);

        $validated = $request->validate([
            'items'                                        => 'required|array|min:1',
            'items.*.item_id'                              => 'required|integer',
            'items.*.received_quantity'                    => 'nullable|integer|min:0',
            'items.*.batch_no'                             => 'nullable|string|max:100',
            'items.*.expiry_date'                          => 'nullable|date',
            'items.*.cartons'                              => 'nullable|array',
            'items.*.cartons.*.carton_code'                => 'nullable|string|max:100',
            'items.*.cartons.*.boxes_per_carton'           => 'nullable|integer|min:1',
            'items.*.cartons.*.units_per_box'              => 'nullable|integer|min:1',
            'items.*.cartons.*.unit_cost'                  => 'nullable|numeric|min:0',
            'items.*.cartons.*.batch_no'                   => 'nullable|string|max:100',
            'items.*.cartons.*.expiry_date'                => 'nullable|date',
        ]);

        $inventoryService->receivePurchaseOrder($purchaseOrder, $validated['items']);

        Session::flash('success', 'Stock received successfully.');

        return redirect()->route('purchaseOrder.receive', $purchaseOrder->id);
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:purchase_orders,id',
        ]);

        $purchaseOrder = PurchaseOrder::query()
            ->with('items')
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($validated['id']);

        if ($purchaseOrder->items->sum('received_quantity') > 0) {
            return response()->json([
                'message' => 'Purchase orders with received stock cannot be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($purchaseOrder) {
            SupplierLedger::removePurchaseOrderEntries($purchaseOrder);
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();
        });

        return response()->json(['success' => 'Purchase order deleted successfully.']);
    }

    private function normalizeCartonPlan(array $rows): ?array
    {
        $clean = collect($rows)->filter(fn ($r) =>
            !empty($r['boxes']) && (int) $r['boxes'] > 0 &&
            !empty($r['units_per_box']) && (int) $r['units_per_box'] > 0
        )->map(fn ($r) => [
            'name'         => trim((string) ($r['name'] ?? '')),
            'boxes'        => (int) $r['boxes'],
            'units_per_box'=> (int) $r['units_per_box'],
            'subtotal'     => (int) $r['boxes'] * (int) $r['units_per_box'],
            'unit_cost'    => isset($r['unit_cost']) && $r['unit_cost'] !== '' ? (float) $r['unit_cost'] : null,
            'batch_no'     => trim((string) ($r['batch_no'] ?? '')),
            'expiry_date'  => $r['expiry_date'] ?? null,
        ])->values()->all();

        return empty($clean) ? null : $clean;
    }

    public function skuSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $skus = Sku::query()
            ->with('product:id,name')
            ->where('status', 'active')
            ->where('track_stock', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('sku_code', 'like', "%{$q}%")
                       ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$q}%"));
                });
            })
            ->select(['id', 'sku_code', 'product_id', 'retail_price'])
            ->orderBy('sku_code')
            ->limit(40)
            ->get();

        return response()->json([
            'results' => $skus->map(fn (Sku $sku) => [
                'id'           => $sku->id,
                'text'         => $sku->display_name,
                'retail_price' => (float) ($sku->retail_price ?? 0),
                'mrp'          => (float) ($sku->retail_price ?? 0), // retail_price = MRP
            ]),
        ]);
    }

    private function formDependencies(?PurchaseOrder $purchaseOrder = null): array
    {
        // Only load SKUs that are already in the PO (for edit form display)
        $existingSkus = collect();
        if ($purchaseOrder && $purchaseOrder->exists) {
            $existingSkuIds = $purchaseOrder->items->pluck('sku_id')->filter()->unique()->values();
            if ($existingSkuIds->isNotEmpty()) {
                $existingSkus = Sku::query()
                    ->with('product:id,name')
                    ->whereIn('id', $existingSkuIds)
                    ->select(['id', 'sku_code', 'product_id'])
                    ->get();
            }
        }

        return [
            'branches'  => $this->branchContext()->accessibleBranches(auth()->user()),
            'warehouses'=> $this->branchContext()->accessibleWarehouses(auth()->user()),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'skus'      => $existingSkus,
        ];
    }

    private function validatedPayload(Request $request, ?int $purchaseOrderId = null): array
    {
        $items = collect($request->input('items', []))
            ->filter(function (array $item) {
                return !empty($item['sku_id']) || !empty($item['quantity']) || !empty($item['unit_cost']);
            })
            ->values()
            ->all();

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'purchase_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('purchase_orders', 'purchase_no')->ignore($purchaseOrderId),
            ],
            'invoice_no'         => 'nullable|string|max:100',
            'branch_id'          => 'required|exists:branches,id',
            'warehouse_id'       => 'required|exists:warehouses,id',
            'supplier_id'        => 'required|exists:suppliers,id',
            'purchase_date'      => 'required|date',
            'status'             => 'required|in:draft,ordered,canceled',
            'other_charge_total' => 'nullable|numeric|min:0',
            'paid_total'         => 'nullable|numeric|min:0',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.sku_id'     => 'required|exists:product_skus,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.bonus_qty'  => 'nullable|integer|min:0',
            'items.*.unit_cost'   => 'required|numeric|min:0',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
            'items.*.mrp'         => 'nullable|numeric|min:0',
            'items.*.sale_price' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount'      => 'nullable|numeric|min:0',
            'items.*.batch_no'        => 'nullable|string|max:100',
            'items.*.expiry_date'     => 'nullable|date',
            'items.*.carton_plan'                    => 'nullable|array',
            'items.*.carton_plan.*.name'             => 'nullable|string|max:100',
            'items.*.carton_plan.*.boxes'            => 'nullable|integer|min:1',
            'items.*.carton_plan.*.units_per_box'    => 'nullable|integer|min:1',
            'items.*.carton_plan.*.unit_cost'        => 'nullable|numeric|min:0',
            'items.*.carton_plan.*.batch_no'         => 'nullable|string|max:100',
            'items.*.carton_plan.*.expiry_date'      => 'nullable|date',
        ]);

        $this->validateBranchWarehouseAccess((int) $validated['branch_id'], (int) $validated['warehouse_id']);

        $skuIds = collect($validated['items'])->pluck('sku_id');
        if ($skuIds->count() !== $skuIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Add each SKU only once per purchase order.',
            ]);
        }

        $subTotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        $orderItems = collect($validated['items'])->map(function (array $item) use (&$subTotal, &$discountTotal, &$taxTotal) {
            $quantity = (int) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];
            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $grossTotal = $quantity * $unitCost;
            $lineTotal = $grossTotal - $discountAmount + $taxAmount;

            $subTotal += $grossTotal;
            $discountTotal += $discountAmount;
            $taxTotal += $taxAmount;

            $salePrice = !empty($item['sale_price']) ? (float) $item['sale_price'] : null;
            if ($salePrice === null && !empty($item['mrp'])) {
                $salePrice = (float) $item['mrp'];
            }

            return [
                'sku_id'            => (int) $item['sku_id'],
                'quantity'          => $quantity,
                'bonus_qty'         => (int) ($item['bonus_qty'] ?? 0),
                'received_quantity' => 0,
                'returned_quantity' => 0,
                'unit_cost'         => $unitCost,
                'unit_price'        => !empty($item['unit_price']) ? (float) $item['unit_price'] : null,
                'mrp'               => !empty($item['mrp']) ? (float) $item['mrp'] : null,
                'sale_price'        => $salePrice,
                'discount_amount'   => $discountAmount,
                'tax_amount'        => $taxAmount,
                'line_total'        => $lineTotal,
                'expiry_date'       => $item['expiry_date'] ?? null,
                'batch_no'          => $item['batch_no'] ?? null,
                'carton_plan'       => $this->normalizeCartonPlan($item['carton_plan'] ?? []),
            ];
        })->all();

        $otherChargeTotal = (float) ($validated['other_charge_total'] ?? 0);
        $paidTotal = (float) ($validated['paid_total'] ?? 0);
        $grandTotal = $subTotal - $discountTotal + $taxTotal + $otherChargeTotal;
        $dueTotal = max($grandTotal - $paidTotal, 0);

        return [
            'purchase_order' => [
                'branch_id' => (int) $validated['branch_id'],
                'warehouse_id' => (int) $validated['warehouse_id'],
                'supplier_id' => (int) $validated['supplier_id'],
                'purchase_no'  => $validated['purchase_no'],
                'invoice_no'   => $validated['invoice_no'] ?? null,
                'purchase_date'=> $validated['purchase_date'],
                'status' => $validated['status'],
                'sub_total' => $subTotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'other_charge_total' => $otherChargeTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidTotal,
                'due_total' => $dueTotal,
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ],
            'items' => $orderItems,
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

    private function generatePurchaseNumber(): string
    {
        $prefix = 'PO-' . now()->format('Ymd') . '-';
        $lastId = (int) PurchaseOrder::query()->max('id') + 1;

        return $prefix . str_pad((string) $lastId, 4, '0', STR_PAD_LEFT);
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            PurchaseOrder::STATUS_DRAFT => '<label class="btn btn-secondary">Draft</label>',
            PurchaseOrder::STATUS_ORDERED => '<label class="btn btn-primary">Ordered</label>',
            PurchaseOrder::STATUS_PARTIAL => '<label class="btn btn-warning">Partial</label>',
            PurchaseOrder::STATUS_RECEIVED => '<label class="btn btn-success">Received</label>',
            PurchaseOrder::STATUS_CANCELED => '<label class="btn btn-danger">Canceled</label>',
            default => '<label class="btn btn-light">' . e(ucfirst($status)) . '</label>',
        };
    }
}
