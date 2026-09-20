<?php

namespace App\Services;

use App\Models\InterBranchTransfer;
use App\Models\InterBranchTransferItem;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterBranchTransferService
{
    public function dispatch(InterBranchTransfer $transfer, array $items, ?int $userId = null): InterBranchTransfer
    {
        return DB::transaction(function () use ($transfer, $items, $userId) {
            $transfer = InterBranchTransfer::query()
                ->with(['items.batch', 'fromWarehouse', 'fromBranch', 'toWarehouse', 'toBranch'])
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($transfer->status !== InterBranchTransfer::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only approved transfers can be dispatched.',
                ]);
            }

            $itemMap = collect($items)->keyBy(fn (array $item) => (int) $item['item_id']);
            $hasDispatchedQuantity = false;
            $sourceWarehouse = $transfer->fromWarehouse;

            foreach ($transfer->items as $transferItem) {
                $row = $itemMap->get((int) $transferItem->id, []);
                $dispatchQuantity = (int) ($row['dispatched_quantity'] ?? 0);

                if ($dispatchQuantity <= 0) {
                    $transferItem->dispatched_quantity = 0;
                    $transferItem->save();
                    continue;
                }

                if ($dispatchQuantity > (int) $transferItem->approved_quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Dispatched quantity cannot exceed approved quantity.',
                    ]);
                }

                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail((int) $transferItem->batch_id);

                if ((int) $batch->warehouse_id !== (int) $transfer->from_warehouse_id) {
                    throw ValidationException::withMessages([
                        'items' => 'Selected batch does not belong to the source warehouse.',
                    ]);
                }

                $stockBalance = $this->findOrCreateStockBalance(
                    (int) $transfer->from_branch_id,
                    (int) $transfer->from_warehouse_id,
                    (int) $transferItem->sku_id,
                    (int) $batch->id
                );

                $availableQuantity = (int) $stockBalance->available_quantity;
                $allowNegative = (bool) ($sourceWarehouse?->allow_negative_stock ?? false);

                if (!$allowNegative && $dispatchQuantity > $availableQuantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Transfer quantity exceeds available stock in the source warehouse.',
                    ]);
                }

                $stockBalance->available_quantity = $availableQuantity - $dispatchQuantity;
                $stockBalance->updated_at = now();
                $stockBalance->save();

                $batch->available_quantity = (int) $batch->available_quantity - $dispatchQuantity;
                $batch->save();

                InventoryTransaction::query()->create([
                    'branch_id' => (int) $transfer->from_branch_id,
                    'warehouse_id' => (int) $transfer->from_warehouse_id,
                    'sku_id' => (int) $transferItem->sku_id,
                    'batch_id' => (int) $batch->id,
                    'reference_type' => 'inter_branch_transfer',
                    'reference_id' => (int) $transfer->id,
                    'movement_type' => 'transfer_out',
                    'quantity' => -$dispatchQuantity,
                    'balance_after' => (int) $stockBalance->available_quantity,
                    'unit_cost' => $batch->purchase_price,
                    'remarks' => 'Stock transfer dispatched: ' . $transfer->transfer_no,
                    'occurred_at' => now(),
                    'created_by' => $userId,
                ]);

                $transferItem->dispatched_quantity = $dispatchQuantity;
                $transferItem->save();
                $hasDispatchedQuantity = true;
            }

            if (!$hasDispatchedQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'Enter at least one dispatched quantity greater than zero.',
                ]);
            }

            $transfer->status = InterBranchTransfer::STATUS_DISPATCHED;
            $transfer->dispatched_at = now();
            $transfer->save();

            return $transfer->fresh(['items.batch.sku.product', 'fromBranch', 'toBranch', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function receive(InterBranchTransfer $transfer, array $items, ?string $discrepancyNote = null, ?int $userId = null): InterBranchTransfer
    {
        return DB::transaction(function () use ($transfer, $items, $discrepancyNote, $userId) {
            $transfer = InterBranchTransfer::query()
                ->with(['items.batch', 'toWarehouse', 'fromBranch', 'toBranch'])
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($transfer->status !== InterBranchTransfer::STATUS_DISPATCHED) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only dispatched transfers can be received.',
                ]);
            }

            $itemMap = collect($items)->keyBy(fn (array $item) => (int) $item['item_id']);
            $hasReceivedQuantity = false;
            $hasDiscrepancy = false;

            foreach ($transfer->items as $transferItem) {
                $row = $itemMap->get((int) $transferItem->id, []);
                $receiveQuantity = (int) ($row['received_quantity'] ?? 0);

                if ($receiveQuantity < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Received quantity cannot be negative.',
                    ]);
                }

                if ($receiveQuantity > (int) $transferItem->dispatched_quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Received quantity cannot exceed dispatched quantity.',
                    ]);
                }

                if ($receiveQuantity === 0) {
                    if ((int) $transferItem->dispatched_quantity > 0) {
                        $hasDiscrepancy = true;
                    }
                    $transferItem->received_quantity = 0;
                    $transferItem->save();
                    continue;
                }

                $sourceBatch = InventoryBatch::query()->lockForUpdate()->findOrFail((int) $transferItem->batch_id);
                $destinationBatch = $this->findOrCreateBatch([
                    'sku_id' => (int) $transferItem->sku_id,
                    'supplier_id' => $sourceBatch->supplier_id,
                    'warehouse_id' => (int) $transfer->to_warehouse_id,
                    'batch_no' => $sourceBatch->batch_no,
                    'purchase_price' => $sourceBatch->purchase_price,
                    'expiry_date' => $sourceBatch->expiry_date,
                ]);

                $destinationBatch->received_quantity = (int) $destinationBatch->received_quantity + $receiveQuantity;
                $destinationBatch->available_quantity = (int) $destinationBatch->available_quantity + $receiveQuantity;
                if (empty($destinationBatch->received_at)) {
                    $destinationBatch->received_at = now();
                }
                $destinationBatch->save();

                $stockBalance = $this->findOrCreateStockBalance(
                    (int) $transfer->to_branch_id,
                    (int) $transfer->to_warehouse_id,
                    (int) $transferItem->sku_id,
                    (int) $destinationBatch->id
                );

                $stockBalance->available_quantity = (int) $stockBalance->available_quantity + $receiveQuantity;
                $stockBalance->updated_at = now();
                $stockBalance->save();

                InventoryTransaction::query()->create([
                    'branch_id' => (int) $transfer->to_branch_id,
                    'warehouse_id' => (int) $transfer->to_warehouse_id,
                    'sku_id' => (int) $transferItem->sku_id,
                    'batch_id' => (int) $destinationBatch->id,
                    'reference_type' => 'inter_branch_transfer',
                    'reference_id' => (int) $transfer->id,
                    'movement_type' => 'transfer_in',
                    'quantity' => $receiveQuantity,
                    'balance_after' => (int) $stockBalance->available_quantity,
                    'unit_cost' => $destinationBatch->purchase_price,
                    'remarks' => 'Stock transfer received: ' . $transfer->transfer_no,
                    'occurred_at' => now(),
                    'created_by' => $userId,
                ]);

                $transferItem->received_quantity = $receiveQuantity;
                $transferItem->save();

                if ($receiveQuantity !== (int) $transferItem->dispatched_quantity) {
                    $hasDiscrepancy = true;
                }

                $hasReceivedQuantity = true;
            }

            if (!$hasReceivedQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'Enter at least one received quantity greater than zero.',
                ]);
            }

            if ($hasDiscrepancy && blank($discrepancyNote)) {
                throw ValidationException::withMessages([
                    'discrepancy_note' => 'Discrepancy note is required when received quantity differs from dispatched quantity.',
                ]);
            }

            $transfer->status = InterBranchTransfer::STATUS_RECEIVED;
            $transfer->received_at = now();
            $transfer->received_by = $userId;
            $transfer->discrepancy_note = $discrepancyNote;
            $transfer->save();

            return $transfer->fresh(['items.batch.sku.product', 'fromBranch', 'toBranch', 'fromWarehouse', 'toWarehouse']);
        });
    }

    private function findOrCreateStockBalance(int $branchId, int $warehouseId, int $skuId, int $batchId): StockBalance
    {
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
}
