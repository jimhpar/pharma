<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\ShipmentZone;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PosCustomerController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function customerSearch(Request $request)
    {
        $query = trim((string) $request->get('query'));

        if ($query === '') {
            return response()->json([]);
        }

        $hasShipmentZoneColumn = Schema::hasColumn('customers', 'shipment_zone_id');

        $customers = Customer::with(['user', 'shipmentZone', 'customerGroup'])
            ->where(function ($customerQuery) use ($query) {
                $customerQuery->where('phone', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('customer_code', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%");
                    });
            })
            ->latest()
            ->limit(8)
            ->get();

        $customers = $customers->map(function ($customer) use ($hasShipmentZoneColumn) {
            $displayName = $customer->name ?: ($customer->user->name ?? $customer->phone);

            return [
                'id' => $customer->id,
                'name' => $displayName,
                'phone' => $customer->phone,
                'billing_address' => $customer->billing_address,
                'shipping_address' => $customer->shipping_address,
                'customer_code' => $customer->customer_code,
                'customer_group' => $customer->customerGroup?->name,
                'current_due' => (float) $customer->current_due,
                'credit_limit' => $customer->credit_limit !== null ? (float) $customer->credit_limit : null,
                'is_member' => (bool) $customer->is_member,
                'note' => $customer->note,
                'shipment_zone_id' => $hasShipmentZoneColumn ? $customer->shipment_zone_id : null,
                'shipment_zone_name' => $customer->shipmentZone?->name,
                'shipment_zone_charge' => (float) ($customer->shipmentZone?->charge ?? 0),
              
            ];
        });

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:30|unique:customers,phone',
            'email' => 'nullable|email|max:255|unique:users,email',
            'billing_address' => 'nullable|string|max:1000',
            'shipping_address' => 'nullable|string|max:1000',
            'shipment_zone_id' => 'nullable|exists:shipping_zones,id',
            'is_member' => 'nullable|boolean',
        ]);

        $loyaltySettings = $this->loyaltyService->getSettings();
        $shouldMakeMember = $loyaltySettings['enabled'] && $request->boolean('is_member');
        $displayName = trim((string) ($validated['name'] ?? '')) !== ''
            ? trim((string) $validated['name'])
            : $validated['phone'];

        $email = $validated['email'] ?? ('customer-' . Str::lower(Str::random(10)) . '@local.test');

        $user = User::create([
            'name' => $displayName,
            'email' => $email,
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'name' => $displayName,
            'email' => $validated['email'] ?? $email,
            'phone' => $validated['phone'],
            'billing_address' => $validated['billing_address'] ?? null,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'user_id' => $user->id,
            'customer_code' => $this->generateCustomerCode(),
            'opening_balance' => 0,
            'credit_limit' => null,
            'current_due' => 0,
            'loyalty_points' => 0,
            'is_member' => $shouldMakeMember,
            'membership_started_at' => $shouldMakeMember ? now() : null,
            'status' => 'active',
        ]);

        CustomerLedger::syncOpeningBalance($customer);

        $customer->load(['user', 'shipmentZone']);
        $shipmentZone = !empty($validated['shipment_zone_id'])
            ? ShipmentZone::query()->find($validated['shipment_zone_id'])
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => $customer,
            'id' => $customer->id,
            'name' => $customer->name ?: ($customer->user->name ?? $customer->phone),
            'phone' => $customer->phone,
            'billing_address' => $customer->billing_address,
            'shipping_address' => $customer->shipping_address,
            'customer_code' => $customer->customer_code,
            'customer_group' => $customer->customerGroup?->name,
            'current_due' => (float) $customer->current_due,
            'credit_limit' => $customer->credit_limit !== null ? (float) $customer->credit_limit : null,
            'is_member' => (bool) $customer->is_member,
            'member_label' => $customer->is_member ? 'Member' : 'Regular',
            'shipment_zone_id' => $validated['shipment_zone_id'] ?? null,
            'shipment_zone_name' => $shipmentZone?->name,
            'shipment_zone_charge' => (float) ($shipmentZone?->charge ?? 0),
        ]);
    }

    public function updateNote(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['note' => $request->input('note', '')]);
        return response()->json(['success' => true]);
    }

    private function generateCustomerCode(): string
    {
        $nextId = (int) Customer::query()->max('id') + 1;

        return 'CUS-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
