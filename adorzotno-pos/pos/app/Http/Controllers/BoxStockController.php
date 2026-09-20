<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\InventoryBox;
use App\Models\Sku;
use App\Services\PosInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoxStockController extends Controller
{
    public function __construct(private PosInventoryService $posInventoryService)
    {
    }

    /** Return batch-level stock for a SKU in the current warehouse. */
    public function batchesForSku(Request $request, int $skuId): JsonResponse
    {
        $warehouseId = (int) $request->warehouse_id;
        $sku = Sku::query()->findOrFail($skuId);

        $batches = InventoryBatch::query()
            ->with(['stockBalances' => fn ($q) => $q->where('warehouse_id', $warehouseId)])
            ->where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->where('available_quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('received_at')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryBatch $batch) use ($sku, $warehouseId) {
                $stockBalance = $batch->stockBalances->first();
                $available = $stockBalance
                    ? max(0, (int) $stockBalance->available_quantity - (int) $stockBalance->reserved_quantity)
                    : max(0, (int) $batch->available_quantity);

                if ($available <= 0) {
                    return null;
                }

                $batchSalePrice = $batch->sale_price !== null && (float) $batch->sale_price > 0
                    ? (float) $batch->sale_price
                    : null;
                $fallbackPrice = $this->posInventoryService->getSkuSellingPrice($sku);

                return [
                    'id' => $batch->id,
                    'batch_no' => $batch->batch_no ?: ('Batch #' . $batch->id),
                    'sale_price' => $batchSalePrice ?? $fallbackPrice,
                    'purchase_price' => (float) ($batch->purchase_price ?? 0),
                    'available_quantity' => $available,
                    'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                    'received_at' => $batch->received_at?->format('Y-m-d'),
                    'warehouse_id' => $warehouseId,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'batches' => $batches,
            'summary' => [
                'total_batches' => $batches->count(),
                'total_units' => $batches->sum('available_quantity'),
            ],
        ]);
    }

    /** Return box-level stock for a SKU in the current warehouse. */
    public function forSku(Request $request, int $skuId): JsonResponse
    {
        $warehouseId = (int) $request->warehouse_id;
        $batchId = $request->integer('batch_id') ?: null;

        $boxes = InventoryBox::query()
            ->with(['carton:id,carton_code,boxes_per_carton,units_per_box,received_at,batch_id'])
            ->whereHas('carton', fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where('sku_id', $skuId)
            ->when($batchId, fn ($q) => $q->whereHas('carton', fn ($cq) => $cq->where('batch_id', $batchId)))
            ->whereIn('status', ['open', 'sealed'])
            ->where('units_available', '>', 0)
            ->orderByRaw("FIELD(status, 'open', 'sealed')")
            ->orderBy('id')
            ->get()
            ->map(fn (InventoryBox $b) => [
                'id'              => $b->id,
                'box_code'        => $b->box_code,
                'carton_code'     => $b->carton?->carton_code,
                'box_number'      => $b->box_number,
                'units_available' => $b->units_available,
                'units_received'  => $b->units_received,
                'units_sold'      => $b->units_sold,
                'batch_id'        => $b->carton?->batch_id,
                'status'          => $b->status,
                'status_color'    => $b->status_color,
                'status_bg'       => $b->status_bg,
                'received_at'     => $b->carton?->received_at?->format('Y-m-d'),
            ]);

        $summary = [
            'total_boxes'    => $boxes->count(),
            'total_units'    => $boxes->sum('units_available'),
            'open_boxes'     => $boxes->where('status', 'open')->count(),
            'sealed_boxes'   => $boxes->where('status', 'sealed')->count(),
        ];

        return response()->json(['boxes' => $boxes, 'summary' => $summary]);
    }
}
