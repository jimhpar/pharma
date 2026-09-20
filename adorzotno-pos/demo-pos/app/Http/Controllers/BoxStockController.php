<?php

namespace App\Http\Controllers;

use App\Models\InventoryBox;
use App\Models\InventoryCarton;
use App\Support\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoxStockController extends Controller
{
    /** Return box-level stock for a SKU in the current warehouse. */
    public function forSku(Request $request, int $skuId): JsonResponse
    {
        $warehouseId = (int) $request->warehouse_id;

        $boxes = InventoryBox::query()
            ->with(['carton:id,carton_code,boxes_per_carton,units_per_box,received_at,batch_id'])
            ->whereHas('carton', fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where('sku_id', $skuId)
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
