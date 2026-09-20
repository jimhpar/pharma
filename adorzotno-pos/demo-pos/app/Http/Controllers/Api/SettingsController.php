<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use App\Models\ShipmentZone;
use Illuminate\Http\JsonResponse;

class SettingsController extends BaseApiController
{
    /**
     * Get store settings
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $settings = [
                'store_name' => Setting::get('company_name', Setting::get('store_name', 'Adorzotno')),
                'store_email' => Setting::get('email', Setting::get('store_email', 'support@adorzotno.health')),
                'store_phone' => Setting::get('phone', Setting::get('store_phone', '+1-800-ADORZOTNO')),
                'store_address' => Setting::get('office_address', Setting::get('store_address', 'H# 18/A (4th Floor), R# Avenue-1, Block# C, Mirpur-2, Dhaka-1216')),
                'store_hours' => Setting::get('store_hours', '9:00 AM - 9:00 PM'),
                'currency' => Setting::get('currency', 'BDT'),
                'currency_symbol' => Setting::get('currency_symbol', '৳'),
            ];

            return $this->success($settings, 'Settings retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve settings: ' . $e->getMessage());
        }
    }

    /**
     * Get shipping information
     *
     * @return JsonResponse
     */
    public function shippingInfo(): JsonResponse
    {
        try {
            $zones = ShipmentZone::query()
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhereRaw('LOWER(status) = ?', ['active']);
                })
                ->orderBy('charge')
                ->orderBy('name')
                ->get(['id', 'name', 'charge', 'status']);

            $defaultShippingCost = Setting::get(
                'default_shipping_cost',
                $zones->isNotEmpty() ? (float) $zones->min('charge') : 0
            );

            $shippingInfo = [
                'default_shipping_cost' => (float) $defaultShippingCost,
                'free_shipping_threshold' => (float) Setting::get('free_shipping_threshold', 5000),
                'zones' => $zones->map(fn ($zone) => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'charge' => (float) $zone->charge,
                    'status' => $zone->status,
                ])->values(),
            ];

            return $this->success($shippingInfo, 'Shipping information retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve shipping information: ' . $e->getMessage());
        }
    }
}
