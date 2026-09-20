<?php

namespace App\Http\Controllers\Api;

use App\Models\ShipmentZone;
use Illuminate\Http\JsonResponse;

class ShipmentZoneController extends BaseApiController
{
    public function index(): JsonResponse
    {
        try {
            $zones = ShipmentZone::query()
                ->whereRaw('LOWER(status) = ?', ['active'])
                ->orderBy('charge')
                ->orderBy('name')
                ->get(['id', 'name', 'charge', 'status'])
                ->map(fn (ShipmentZone $zone) => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'charge' => (float) $zone->charge,
                    'status' => strtolower((string) $zone->status),
                ])
                ->values();

            return $this->success([
                'shipment_zones' => $zones,
            ], 'Shipment zones retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve shipment zones: ' . $e->getMessage());
        }
    }
}
