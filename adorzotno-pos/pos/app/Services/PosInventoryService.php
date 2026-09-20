<?php

namespace App\Services;

use App\Models\BranchPrice;
use App\Models\InventoryBatch;
use App\Models\InventoryBox;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\InventoryTransaction;
use App\Models\Sku;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Support\BranchContext;
use RuntimeException;

class PosInventoryService
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function resolveWarehouse(?int $branchId = null): ?Warehouse
    {
        $branchId ??= $this->branchContext->currentBranchId(auth()->user());

        if ($branchId !== null) {
            return Warehouse::query()
                ->with('branch')
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first()
                ?? Warehouse::query()
                    ->with('branch')
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();
        }

        return Warehouse::query()
            ->with('branch')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? Warehouse::query()
                ->with('branch')
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
    }

    public function getSkuAvailableStock(int $skuId, ?Warehouse $warehouse = null): int
    {
        $warehouse ??= $this->resolveWarehouse();

        $query = StockBalance::query()->where('sku_id', $skuId);

        if ($warehouse !== null) {
            $query->where('warehouse_id', $warehouse->id);
        }

        $available = $query->get(['available_quantity', 'reserved_quantity'])
            ->sum(function (StockBalance $stockBalance) {
                return max(0, (int) $stockBalance->available_quantity - (int) $stockBalance->reserved_quantity);
            });

        return max(0, (int) $available);
    }

    public function getSkuSellingPrice(Sku $sku, ?Warehouse $warehouse = null): float
    {
        $warehouse ??= $this->resolveWarehouse();
        $branchPrice = $this->resolveActiveBranchPrice($sku, $warehouse?->branch_id);

        // FIFO batch sale_price — oldest available batch for this warehouse
        $batchSalePrice = null;
        if ($warehouse) {
            $batchSalePrice = InventoryBatch::query()
                ->where('sku_id', $sku->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('available_quantity', '>', 0)
                ->whereNotNull('sale_price')
                ->where('sale_price', '>', 0)
                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date')
                ->orderBy('received_at')
                ->value('sale_price');
        }

        $candidatePrices = [
            // FIFO batch sale_price: price of the actual stock that will be sold next
            $batchSalePrice,
            // SKU-level fallback (retail_price, branch price, etc.)
            $branchPrice?->retail_price,
            $branchPrice?->online_price,
            $sku->getRawOriginal('retail_price'),
            $sku->getRawOriginal('online_price'),
            $branchPrice?->minimum_selling_price,
            $sku->getRawOriginal('minimum_selling_price'),
        ];

        foreach ($candidatePrices as $candidatePrice) {
            if ($candidatePrice !== null && $candidatePrice !== '') {
                return (float) $candidatePrice;
            }
        }

        return 0.0;
    }

    public function deductSkuStockForSale(
        int $skuId,
        int $branchId,
        int $warehouseId,
        int $salesOrderId,
        int $quantity,
        ?int $createdBy = null,
        ?string $orderNo = null,
        ?int $preferredBoxId = null,
        ?int $preferredBatchId = null
    ): array {
        $remainingQuantity = $quantity;
        $allocations = [];
        $warehouse = Warehouse::query()->find($warehouseId);
        $allowNegative = (bool) ($warehouse?->allow_negative_stock ?? false);
        $sku = Sku::query()->find($skuId);

        $batches = InventoryBatch::query()
            ->where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->where('available_quantity', '>', 0)
            ->when($preferredBatchId, fn ($q) => $q->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$preferredBatchId]))
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $stockBalance = StockBalance::query()->lockForUpdate()->firstOrNew([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'sku_id' => $skuId,
                'batch_id' => $batch->id,
            ], [
                'available_quantity' => 0,
                'reserved_quantity' => 0,
                'reorder_level' => 0,
                'updated_at' => now(),
            ]);

            $sellableQuantity = min(
                max(0, (int) $batch->available_quantity),
                max(0, (int) $stockBalance->available_quantity - (int) $stockBalance->reserved_quantity)
            );

            if ($sellableQuantity <= 0) {
                continue;
            }

            $deductQuantity = min($remainingQuantity, $sellableQuantity);

            $stockBalance->available_quantity = (int) $stockBalance->available_quantity - $deductQuantity;
            $stockBalance->updated_at = now();
            $stockBalance->save();

            $batch->available_quantity = (int) $batch->available_quantity - $deductQuantity;
            $batch->save();

            InventoryTransaction::query()->create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'sku_id' => $skuId,
                'batch_id' => $batch->id,
                'reference_type' => 'sales_order',
                'reference_id' => $salesOrderId,
                'movement_type' => 'sale_out',
                'quantity' => -$deductQuantity,
                'balance_after' => (int) $stockBalance->available_quantity,
                'unit_cost' => $batch->purchase_price,
                'remarks' => $orderNo ? 'Sold via POS order ' . $orderNo : 'Sold via POS',
                'occurred_at' => now(),
                'created_by' => $createdBy,
            ]);

            $allocations[] = [
                'batch_id'   => (int) $batch->id,
                'quantity'   => $deductQuantity,
                'unit_cost'  => (float) $batch->purchase_price,
                'sale_price' => $batch->sale_price !== null ? (float) $batch->sale_price : null,
            ];

            $remainingQuantity -= $deductQuantity;
        }

        if ($remainingQuantity > 0) {
            if (!$allowNegative) {
                throw new RuntimeException('Insufficient inventory batch stock for SKU ' . $skuId . '.');
            }

            $stockBalance = StockBalance::query()->lockForUpdate()->firstOrNew([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'sku_id' => $skuId,
                'batch_id' => null,
            ], [
                'available_quantity' => 0,
                'reserved_quantity' => 0,
                'reorder_level' => 0,
                'updated_at' => now(),
            ]);

            $stockBalance->available_quantity = (int) $stockBalance->available_quantity - $remainingQuantity;
            $stockBalance->updated_at = now();
            $stockBalance->save();

            InventoryTransaction::query()->create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'sku_id' => $skuId,
                'batch_id' => null,
                'reference_type' => 'sales_order',
                'reference_id' => $salesOrderId,
                'movement_type' => 'sale_out',
                'quantity' => -$remainingQuantity,
                'balance_after' => (int) $stockBalance->available_quantity,
                'unit_cost' => (float) ($sku?->cost_price ?? 0),
                'remarks' => $orderNo ? 'Sold via POS order ' . $orderNo . ' (negative stock)' : 'Sold via POS (negative stock)',
                'occurred_at' => now(),
                'created_by' => $createdBy,
            ]);

            $allocations[] = [
                'batch_id' => null,
                'quantity' => $remainingQuantity,
                'unit_cost' => (float) ($sku?->cost_price ?? 0),
            ];

            $remainingQuantity = 0;
        }

        // Also deduct from box-level tracking (preferred box first, then FIFO)
        $this->deductFromBoxes($skuId, $warehouseId, $quantity, $preferredBoxId, $preferredBatchId);

        return $allocations;
    }

    private function deductFromBoxes(int $skuId, int $warehouseId, int $quantity, ?int $preferredBoxId = null, ?int $preferredBatchId = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $baseQuery = fn () => InventoryBox::query()
            ->whereHas('carton', fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where('sku_id', $skuId)
            ->when($preferredBatchId, fn ($q) => $q->whereHas('carton', fn ($cq) => $cq->where('batch_id', $preferredBatchId)))
            ->whereIn('status', ['open', 'sealed'])
            ->where('units_available', '>', 0)
            ->lockForUpdate();

        if ($preferredBoxId) {
            $preferred = $baseQuery()->where('id', $preferredBoxId)->first();
            $others = $baseQuery()
                ->where('id', '!=', $preferredBoxId)
                ->orderByRaw("FIELD(status, 'open', 'sealed')")
                ->orderBy('id')
                ->get();
            $boxes = $preferred ? collect([$preferred])->merge($others) : $others;
        } else {
            $boxes = $baseQuery()
                ->orderByRaw("FIELD(status, 'open', 'sealed')")
                ->orderBy('id')
                ->get();
        }

        $remaining = $quantity;
        foreach ($boxes as $box) {
            if ($remaining <= 0) {
                break;
            }
            $remaining -= $box->deduct($remaining);
        }
        // If $remaining > 0, the stock is sold without box tracking (OK — negative stock or untracked)
    }

    private function resolveActiveBranchPrice(Sku $sku, ?int $branchId): ?BranchPrice
    {
        if (empty($branchId)) {
            return null;
        }

        if ($sku->relationLoaded('branchPrices')) {
            return $sku->branchPrices
                ->first(fn (BranchPrice $branchPrice) => (int) $branchPrice->branch_id === (int) $branchId && (bool) $branchPrice->is_active);
        }

        return $sku->branchPrices()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    public function restoreStockForSalesOrder(
        SalesOrder $salesOrder,
        string $movementType,
        ?int $createdBy = null,
        ?string $remarks = null
    ): void {
        $salesOrder->loadMissing('items.batch');

        foreach ($salesOrder->items as $item) {
            $this->restoreStockForOrderItem($salesOrder, $item, $movementType, $createdBy, $remarks);
        }
    }

    private function restoreStockForOrderItem(
        SalesOrder $salesOrder,
        SalesOrderItem $item,
        string $movementType,
        ?int $createdBy = null,
        ?string $remarks = null
    ): void {
        $batchId = (int) ($item->batch_id ?? 0);
        $restorableQuantity = max(0, (int) $item->quantity - (int) $item->returned_quantity);

        if ($restorableQuantity <= 0) {
            return;
        }

        $batch = null;
        if ($batchId > 0) {
            $batch = InventoryBatch::query()->lockForUpdate()->find($batchId);

            if ($batch === null) {
                throw new RuntimeException('Unable to restore stock because inventory batch #' . $batchId . ' was not found.');
            }
        }

        $stockBalance = StockBalance::query()->lockForUpdate()->firstOrNew([
            'branch_id' => (int) $salesOrder->branch_id,
            'warehouse_id' => (int) ($item->warehouse_id ?? $salesOrder->warehouse_id),
            'sku_id' => (int) $item->sku_id,
            'batch_id' => $batch?->id,
        ], [
            'available_quantity' => 0,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
            'updated_at' => now(),
        ]);

        $stockBalance->available_quantity = (int) $stockBalance->available_quantity + $restorableQuantity;
        $stockBalance->updated_at = now();
        $stockBalance->save();

        if ($batch !== null) {
            $batch->available_quantity = (int) $batch->available_quantity + $restorableQuantity;
            $batch->save();
        }

        InventoryTransaction::query()->create([
            'branch_id' => (int) $salesOrder->branch_id,
            'warehouse_id' => (int) ($item->warehouse_id ?? $salesOrder->warehouse_id),
            'sku_id' => (int) $item->sku_id,
            'batch_id' => $batch?->id,
            'reference_type' => 'sales_order',
            'reference_id' => (int) $salesOrder->id,
            'movement_type' => $movementType,
            'quantity' => $restorableQuantity,
            'balance_after' => (int) $stockBalance->available_quantity,
            'unit_cost' => (float) ($batch?->purchase_price ?? $item->cost_price ?? 0),
            'remarks' => $remarks ?: 'Stock restored for sales order ' . $salesOrder->order_no,
            'occurred_at' => now(),
            'created_by' => $createdBy,
        ]);

        $item->returned_quantity = (int) $item->quantity;
        $item->save();
    }
}
