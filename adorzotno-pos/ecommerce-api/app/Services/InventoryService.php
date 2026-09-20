<?php

namespace App\Services;

use App\Models\InventoryAdjustment;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sku;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function receivePurchaseOrder(PurchaseOrder $purchaseOrder, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $items) {
            $purchaseOrder = PurchaseOrder::query()
                ->with(['items.sku', 'branch', 'warehouse', 'supplier'])
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($purchaseOrder->status === PurchaseOrder::STATUS_CANCELED) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'Canceled purchase orders cannot receive stock.',
                ]);
            }

            $purchaseItems = $purchaseOrder->items->keyBy('id');
            $receivedAny = false;

            foreach ($items as $index => $receivedItem) {
                $itemId = (int) ($receivedItem['item_id'] ?? 0);
                $receiveQuantity = (int) ($receivedItem['received_quantity'] ?? 0);

                if ($receiveQuantity <= 0) {
                    continue;
                }

                /** @var PurchaseOrderItem|null $purchaseOrderItem */
                $purchaseOrderItem = $purchaseItems->get($itemId);
                if ($purchaseOrderItem === null) {
                    throw ValidationException::withMessages([
                        "items.$index.item_id" => 'Purchase order item not found.',
                    ]);
                }

                $remainingQuantity = max(
                    0,
                    (int) $purchaseOrderItem->quantity - (int) $purchaseOrderItem->received_quantity
                );

                if ($receiveQuantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        "items.$index.received_quantity" => 'Receive quantity exceeds remaining quantity.',
                    ]);
                }

                $batchNo = trim((string) ($receivedItem['batch_no'] ?? ''));
                if ($batchNo === '') {
                    $batchNo = trim((string) $purchaseOrderItem->batch_no);
                }
                if ($batchNo === '') {
                    $batchNo = $this->generateBatchNumber($purchaseOrder, $purchaseOrderItem);
                }

                $expiryDate = $receivedItem['expiry_date'] ?? $purchaseOrderItem->expiry_date;

                $batch = $this->findOrCreateBatch([
                    'sku_id' => $purchaseOrderItem->sku_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'batch_no' => $batchNo,
                    'purchase_price' => $purchaseOrderItem->unit_cost,
                    'expiry_date' => $expiryDate ?: null,
                ]);

                $batch->received_quantity = (int) $batch->received_quantity + $receiveQuantity;
                $batch->available_quantity = (int) $batch->available_quantity + $receiveQuantity;
                if (empty($batch->received_at)) {
                    $batch->received_at = now();
                }
                $batch->save();

                $stockBalance = $this->increaseStockBalance(
                    $purchaseOrder->branch_id,
                    $purchaseOrder->warehouse_id,
                    $purchaseOrderItem->sku_id,
                    $batch->id,
                    $receiveQuantity
                );

                $this->recordTransaction([
                    'branch_id' => $purchaseOrder->branch_id,
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'sku_id' => $purchaseOrderItem->sku_id,
                    'batch_id' => $batch->id,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $purchaseOrder->id,
                    'movement_type' => 'purchase_receive',
                    'quantity' => $receiveQuantity,
                    'balance_after' => (int) $stockBalance->available_quantity,
                    'unit_cost' => $purchaseOrderItem->unit_cost,
                    'remarks' => 'Received from purchase order ' . $purchaseOrder->purchase_no,
                    'occurred_at' => now(),
                    'created_by' => auth()->id(),
                ]);

                $purchaseOrderItem->received_quantity = (int) $purchaseOrderItem->received_quantity + $receiveQuantity;
                if (!empty($receivedItem['batch_no'])) {
                    $purchaseOrderItem->batch_no = $batchNo;
                }
                if (!empty($receivedItem['expiry_date'])) {
                    $purchaseOrderItem->expiry_date = $expiryDate;
                }
                $purchaseOrderItem->save();

                $receivedAny = true;
            }

            if ($receivedAny) {
                $this->refreshPurchaseOrderStatus($purchaseOrder);
            }

            return $purchaseOrder->fresh(['items.sku', 'branch', 'warehouse', 'supplier']);
        });
    }

    public function createAdjustment(array $documentData, array $items): InventoryAdjustment
    {
        return DB::transaction(function () use ($documentData, $items) {
            $adjustment = InventoryAdjustment::query()->create($documentData);
            $warehouse = Warehouse::query()->findOrFail((int) $documentData['warehouse_id']);
            $documentType = $documentData['document_type'] ?? InventoryAdjustment::TYPE_ADJUSTMENT;
            $allowNegative = (bool) $warehouse->allow_negative_stock;

            foreach ($items as $index => $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $direction = $this->resolveAdjustmentDirection($documentType, $item['adjustment_type'] ?? null);
                $batchId = !empty($item['batch_id']) ? (int) $item['batch_id'] : null;
                $skuId = !empty($item['sku_id']) ? (int) $item['sku_id'] : null;
                $batch = null;

                if ($batchId !== null) {
                    $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($batchId);

                    if ((int) $batch->warehouse_id !== (int) $documentData['warehouse_id']) {
                        throw ValidationException::withMessages([
                            "items.$index.batch_id" => 'Selected batch does not belong to the selected warehouse.',
                        ]);
                    }

                    $skuId = (int) $batch->sku_id;
                } elseif ($direction === 'increase') {
                    if (empty($skuId)) {
                        throw ValidationException::withMessages([
                            "items.$index.sku_id" => 'Select a SKU or existing batch for incoming stock.',
                        ]);
                    }

                    $sku = Sku::query()->findOrFail($skuId);

                    if (!(bool) $sku->track_stock) {
                        throw ValidationException::withMessages([
                            "items.$index.sku_id" => 'Selected SKU is not stockable.',
                        ]);
                    }

                    $batch = $this->findOrCreateBatch([
                        'sku_id' => $skuId,
                        'supplier_id' => null,
                        'warehouse_id' => (int) $documentData['warehouse_id'],
                        'batch_no' => trim((string) ($item['batch_no'] ?? '')) ?: $this->generateInventoryBatchNumber($adjustment->adjustment_no, $skuId),
                        'purchase_price' => (float) ($item['unit_cost'] ?? $sku->cost_price ?? 0),
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);
                } elseif (!$allowNegative && $batchId === null) {
                    throw ValidationException::withMessages([
                        "items.$index.batch_id" => 'Select an existing batch for outbound stock.',
                    ]);
                }

                $stockBalance = $this->findOrCreateStockBalance(
                    (int) $documentData['branch_id'],
                    (int) $documentData['warehouse_id'],
                    (int) $skuId,
                    $batch?->id
                );

                $beforeBalance = (int) $stockBalance->available_quantity;

                if ($direction === 'decrease' && !$allowNegative && $quantity > $beforeBalance) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => $this->adjustmentQuantityExceededMessage($documentType),
                    ]);
                }

                $delta = $direction === 'increase' ? $quantity : -$quantity;
                $afterBalance = $beforeBalance + $delta;

                $stockBalance->available_quantity = $afterBalance;
                $stockBalance->updated_at = now();
                $stockBalance->save();

                if ($batch !== null) {
                    if ($delta > 0) {
                        $batch->received_quantity = (int) $batch->received_quantity + $delta;
                        if (empty($batch->received_at)) {
                            $batch->received_at = now();
                        }
                    }

                    $batch->available_quantity = (int) $batch->available_quantity + $delta;
                    $batch->save();
                }

                $adjustment->items()->create([
                    'sku_id' => (int) $skuId,
                    'batch_id' => $batch?->id,
                    'adjustment_type' => $direction,
                    'quantity' => $quantity,
                    'balance_before' => $beforeBalance,
                    'balance_after' => $afterBalance,
                    'reason' => $item['reason'] ?? null,
                ]);

                $this->recordTransaction([
                    'branch_id' => $documentData['branch_id'],
                    'warehouse_id' => $documentData['warehouse_id'],
                    'sku_id' => (int) $skuId,
                    'batch_id' => $batch?->id,
                    'reference_type' => 'inventory_adjustment',
                    'reference_id' => $adjustment->id,
                    'movement_type' => $this->resolveAdjustmentMovementType($documentType, $direction),
                    'quantity' => $delta,
                    'balance_after' => $afterBalance,
                    'unit_cost' => $batch?->purchase_price ?? ($item['unit_cost'] ?? 0),
                    'remarks' => $item['reason'] ?? $adjustment->note,
                    'occurred_at' => $documentData['adjustment_date'],
                    'created_by' => $documentData['created_by'] ?? null,
                ]);
            }

            return $adjustment->fresh(['items.batch.sku.product', 'branch', 'warehouse']);
        });
    }

    public function createTransfer(array $documentData, array $items): StockTransfer
    {
        return DB::transaction(function () use ($documentData, $items) {
            $transfer = StockTransfer::query()->create($documentData);
            $sourceWarehouse = Warehouse::query()->findOrFail((int) $documentData['from_warehouse_id']);
            $allowNegative = (bool) $sourceWarehouse->allow_negative_stock;

            foreach ($items as $index => $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $sourceBatch = InventoryBatch::query()->lockForUpdate()->findOrFail((int) $item['source_batch_id']);
                if ((int) $sourceBatch->warehouse_id !== (int) $documentData['from_warehouse_id']) {
                    throw ValidationException::withMessages([
                        "items.$index.source_batch_id" => 'Selected batch does not belong to the source warehouse.',
                    ]);
                }

                $sourceBalance = $this->findOrCreateStockBalance(
                    (int) $documentData['from_branch_id'],
                    (int) $documentData['from_warehouse_id'],
                    (int) $sourceBatch->sku_id,
                    (int) $sourceBatch->id
                );
                $sourceBefore = (int) $sourceBalance->available_quantity;

                if (!$allowNegative && $quantity > $sourceBefore) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Transfer quantity exceeds available stock.',
                    ]);
                }

                $sourceBalance->available_quantity = $sourceBefore - $quantity;
                $sourceBalance->updated_at = now();
                $sourceBalance->save();

                $sourceBatch->available_quantity = (int) $sourceBatch->available_quantity - $quantity;
                $sourceBatch->save();

                $destinationBatch = $this->findOrCreateBatch([
                    'sku_id' => $sourceBatch->sku_id,
                    'supplier_id' => $sourceBatch->supplier_id,
                    'warehouse_id' => $documentData['to_warehouse_id'],
                    'batch_no' => $sourceBatch->batch_no,
                    'purchase_price' => $sourceBatch->purchase_price,
                    'expiry_date' => $sourceBatch->expiry_date,
                ]);
                $destinationBatch->received_quantity = (int) $destinationBatch->received_quantity + $quantity;
                $destinationBatch->available_quantity = (int) $destinationBatch->available_quantity + $quantity;
                if (empty($destinationBatch->received_at)) {
                    $destinationBatch->received_at = now();
                }
                $destinationBatch->save();

                $destinationBalance = $this->increaseStockBalance(
                    (int) $documentData['to_branch_id'],
                    (int) $documentData['to_warehouse_id'],
                    (int) $sourceBatch->sku_id,
                    (int) $destinationBatch->id,
                    $quantity
                );

                $transfer->items()->create([
                    'sku_id' => $sourceBatch->sku_id,
                    'source_batch_id' => $sourceBatch->id,
                    'destination_batch_id' => $destinationBatch->id,
                    'quantity' => $quantity,
                    'note' => $item['note'] ?? null,
                ]);

                $this->recordTransaction([
                    'branch_id' => $documentData['from_branch_id'],
                    'warehouse_id' => $documentData['from_warehouse_id'],
                    'sku_id' => $sourceBatch->sku_id,
                    'batch_id' => $sourceBatch->id,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'movement_type' => 'transfer_out',
                    'quantity' => -$quantity,
                    'balance_after' => (int) $sourceBalance->available_quantity,
                    'unit_cost' => $sourceBatch->purchase_price,
                    'remarks' => $item['note'] ?? $transfer->note,
                    'occurred_at' => $documentData['transfer_date'],
                    'created_by' => $documentData['created_by'] ?? null,
                ]);

                $this->recordTransaction([
                    'branch_id' => $documentData['to_branch_id'],
                    'warehouse_id' => $documentData['to_warehouse_id'],
                    'sku_id' => $sourceBatch->sku_id,
                    'batch_id' => $destinationBatch->id,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'movement_type' => 'transfer_in',
                    'quantity' => $quantity,
                    'balance_after' => (int) $destinationBalance->available_quantity,
                    'unit_cost' => $destinationBatch->purchase_price,
                    'remarks' => $item['note'] ?? $transfer->note,
                    'occurred_at' => $documentData['transfer_date'],
                    'created_by' => $documentData['created_by'] ?? null,
                ]);
            }

            return $transfer->fresh(['items.sourceBatch.sku.product', 'fromBranch', 'fromWarehouse', 'toBranch', 'toWarehouse']);
        });
    }

    public function createSupplierReturn(array $documentData, array $items): SupplierReturn
    {
        return DB::transaction(function () use ($documentData, $items) {
            $supplierReturn = SupplierReturn::query()->create($documentData);
            $warehouse = Warehouse::query()->findOrFail((int) $documentData['warehouse_id']);
            $allowNegative = (bool) $warehouse->allow_negative_stock;

            foreach ($items as $index => $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail((int) $item['batch_id']);
                if ((int) $batch->warehouse_id !== (int) $documentData['warehouse_id']) {
                    throw ValidationException::withMessages([
                        "items.$index.batch_id" => 'Selected batch does not belong to the selected warehouse.',
                    ]);
                }
                if ((int) $batch->supplier_id !== (int) $documentData['supplier_id']) {
                    throw ValidationException::withMessages([
                        "items.$index.batch_id" => 'Selected batch does not belong to the selected supplier.',
                    ]);
                }

                $stockBalance = $this->findOrCreateStockBalance(
                    (int) $documentData['branch_id'],
                    (int) $documentData['warehouse_id'],
                    (int) $batch->sku_id,
                    (int) $batch->id
                );
                $beforeBalance = (int) $stockBalance->available_quantity;

                if (!$allowNegative && $quantity > $beforeBalance) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Return quantity exceeds available stock.',
                    ]);
                }

                $stockBalance->available_quantity = $beforeBalance - $quantity;
                $stockBalance->updated_at = now();
                $stockBalance->save();

                $batch->available_quantity = (int) $batch->available_quantity - $quantity;
                $batch->save();

                $supplierReturn->items()->create([
                    'sku_id' => $batch->sku_id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'unit_cost' => $batch->purchase_price,
                    'reason' => $item['reason'] ?? null,
                ]);

                $this->recordTransaction([
                    'branch_id' => $documentData['branch_id'],
                    'warehouse_id' => $documentData['warehouse_id'],
                    'sku_id' => $batch->sku_id,
                    'batch_id' => $batch->id,
                    'reference_type' => 'supplier_return',
                    'reference_id' => $supplierReturn->id,
                    'movement_type' => 'supplier_return',
                    'quantity' => -$quantity,
                    'balance_after' => (int) $stockBalance->available_quantity,
                    'unit_cost' => $batch->purchase_price,
                    'remarks' => $item['reason'] ?? $supplierReturn->note,
                    'occurred_at' => $documentData['return_date'],
                    'created_by' => $documentData['created_by'] ?? null,
                ]);
            }

            return $supplierReturn->fresh(['items.batch.sku.product', 'branch', 'warehouse', 'supplier']);
        });
    }

    private function increaseStockBalance(
        int $branchId,
        int $warehouseId,
        int $skuId,
        ?int $batchId,
        int $quantity
    ): StockBalance {
        $stockBalance = $this->findOrCreateStockBalance($branchId, $warehouseId, $skuId, $batchId);

        $stockBalance->available_quantity = (int) $stockBalance->available_quantity + $quantity;
        $stockBalance->updated_at = now();
        $stockBalance->save();

        return $stockBalance;
    }

    private function findOrCreateStockBalance(
        int $branchId,
        int $warehouseId,
        int $skuId,
        ?int $batchId
    ): StockBalance {
        return StockBalance::query()->lockForUpdate()->firstOrNew([
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'sku_id' => $skuId,
            'batch_id' => $batchId,
        ], [
            'available_quantity' => 0,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
            'updated_at' => now(),
        ]);
    }

    private function findOrCreateBatch(array $attributes): InventoryBatch
    {
        return InventoryBatch::query()->lockForUpdate()->firstOrNew([
            'sku_id' => $attributes['sku_id'],
            'supplier_id' => $attributes['supplier_id'],
            'warehouse_id' => $attributes['warehouse_id'],
            'batch_no' => $attributes['batch_no'],
            'purchase_price' => $attributes['purchase_price'],
            'expiry_date' => $attributes['expiry_date'],
        ], [
            'received_quantity' => 0,
            'available_quantity' => 0,
            'received_at' => now(),
        ]);
    }

    private function recordTransaction(array $payload): void
    {
        InventoryTransaction::query()->create($payload);
    }

    private function resolveAdjustmentDirection(string $documentType, ?string $direction): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'increase',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'decrease',
            default => $direction === 'decrease' ? 'decrease' : 'increase',
        };
    }

    private function resolveAdjustmentMovementType(string $documentType, string $direction): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_OPENING_STOCK => 'opening_stock',
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'stock_issue',
            default => $direction === 'increase' ? 'adjustment_in' : 'adjustment_out',
        };
    }

    private function adjustmentQuantityExceededMessage(string $documentType): string
    {
        return match ($documentType) {
            InventoryAdjustment::TYPE_STOCK_ISSUE => 'Issue quantity exceeds available stock.',
            default => 'Adjustment quantity exceeds available stock.',
        };
    }

    private function refreshPurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('items');

        $items = $purchaseOrder->items;
        $allReceived = $items->every(function (PurchaseOrderItem $item) {
            return (int) $item->received_quantity >= (int) $item->quantity;
        });

        $someReceived = $items->contains(function (PurchaseOrderItem $item) {
            return (int) $item->received_quantity > 0;
        });

        if ($allReceived) {
            $purchaseOrder->status = PurchaseOrder::STATUS_RECEIVED;
        } elseif ($someReceived) {
            $purchaseOrder->status = PurchaseOrder::STATUS_PARTIAL;
        }

        $purchaseOrder->save();
    }

    private function generateBatchNumber(PurchaseOrder $purchaseOrder, PurchaseOrderItem $purchaseOrderItem): string
    {
        return 'PO' . $purchaseOrder->id
            . '-SKU' . $purchaseOrderItem->sku_id
            . '-' . now()->format('YmdHis');
    }

    private function generateInventoryBatchNumber(string $referenceNo, int $skuId): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $referenceNo))
            . '-SKU' . $skuId
            . '-' . now()->format('YmdHis');
    }
}
