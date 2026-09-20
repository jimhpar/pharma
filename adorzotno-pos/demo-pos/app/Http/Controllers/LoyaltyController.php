<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltyPointLedger;
use App\Models\SalesOrder;
use App\Services\LoyaltyService;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    // ─── Settings ───────────────────────────────────────────────────────────

    public function settingsShow()
    {
        $loyaltySettings = $this->loyaltyService->getSettings();

        return view('loyalty.settings', compact('loyaltySettings'));
    }

    public function settingsSave(Request $request)
    {
        $validated = $request->validate([
            'loyalty_enabled'             => 'nullable|boolean',
            'loyalty_redemption_enabled'  => 'nullable|boolean',
            'loyalty_points_per_amount'   => 'required|numeric|min:1',
            'loyalty_point_value'         => 'required|numeric|min:0.01',
            'loyalty_membership_threshold'=> 'required|numeric|min:0',
        ]);

        $data = [
            'enabled'              => $request->has('loyalty_enabled') ? 1 : 0,
            'redemption_enabled'   => $request->has('loyalty_redemption_enabled') ? 1 : 0,
            'points_per_amount'    => $validated['loyalty_points_per_amount'],
            'point_value'          => $validated['loyalty_point_value'],
            'membership_threshold' => $validated['loyalty_membership_threshold'],
            'updated_at'           => now(),
        ];

        $existing = DB::table('loyalty_settings')->first();
        if ($existing) {
            DB::table('loyalty_settings')->where('id', $existing->id)->update($data);
        } else {
            DB::table('loyalty_settings')->insert(array_merge($data, ['created_at' => now()]));
        }

        return redirect()->route('loyalty.settings')->with('success', 'Loyalty settings saved successfully.');
    }

    // ─── Manual Point Adjustment ─────────────────────────────────────────────

    public function adjustShow()
    {
        $customers = Customer::query()
            ->select(['id', 'name', 'phone', 'customer_code', 'loyalty_points', 'is_member'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('loyalty.adjust', compact('customers'));
    }

    public function adjustStore(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'adjustment'  => 'required|integer|not_in:0',
            'note'        => 'required|string|max:500',
        ]);

        $customer = Customer::query()->findOrFail($validated['customer_id']);

        try {
            $this->loyaltyService->adjustPoints(
                $customer,
                (int) $validated['adjustment'],
                $validated['note'],
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['adjustment' => $e->getMessage()])->withInput();
        }

        return redirect()->route('loyalty.adjust')->with('success', "Points adjusted for {$customer->name}.");
    }

    // ─── Reports ─────────────────────────────────────────────────────────────

    public function reportShow()
    {
        $loyaltySettings = $this->loyaltyService->getSettings();

        $summary = [
            'total_customers_with_points' => Customer::query()->where('loyalty_points', '>', 0)->count(),
            'total_member_customers'       => Customer::query()->where('is_member', true)->count(),
            'total_points_in_circulation'  => Customer::query()->sum('loyalty_points'),
            'total_lifetime_earned'        => Customer::query()->sum('lifetime_earned_points'),
            'total_lifetime_redeemed'      => Customer::query()->sum('lifetime_redeemed_points'),
            'total_point_discount_given'   => SalesOrder::query()->sum('point_discount_amount'),
        ];

        return view('loyalty.report', compact('loyaltySettings', 'summary'));
    }

    public function reportList(Request $request): JsonResponse
    {
        $type = $request->get('report_type', 'customers');

        if ($type === 'ledger') {
            $query = LoyaltyPointLedger::query()
                ->with(['customer:id,name,phone', 'salesOrder:id,order_no', 'createdByUser:id,name'])
                ->orderByDesc('created_at');

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->integer('customer_id'));
            }

            $rows = $query->paginate(50);

            return response()->json([
                'data' => $rows->map(fn ($r) => [
                    'id'           => $r->id,
                    'date'         => DateFormatter::dateTime($r->created_at),
                    'customer'     => $r->customer?->name . ' (' . $r->customer?->phone . ')',
                    'type_badge'   => $this->typeBadge($r->type),
                    'points'       => ($r->points > 0 ? '+' : '') . $r->points,
                    'amount_value' => Currency::format($r->amount_value),
                    'order_no'     => $r->salesOrder?->order_no ?? '—',
                    'note'         => $r->note,
                    'created_by'   => $r->createdByUser?->name ?? '—',
                ]),
                'meta' => [
                    'total'        => $rows->total(),
                    'current_page' => $rows->currentPage(),
                    'last_page'    => $rows->lastPage(),
                ],
            ]);
        }

        // Default: customer points balance list
        $customers = Customer::query()
            ->select(['id', 'name', 'phone', 'customer_code', 'loyalty_points', 'lifetime_earned_points', 'lifetime_redeemed_points', 'total_purchase_amount', 'is_member', 'membership_started_at'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sq) use ($request) {
                $sq->where('name', 'like', '%' . $request->get('search') . '%')
                   ->orWhere('phone', 'like', '%' . $request->get('search') . '%')
                   ->orWhere('customer_code', 'like', '%' . $request->get('search') . '%');
            }))
            ->when($request->boolean('members_only'), fn ($q) => $q->where('is_member', true))
            ->orderByDesc('loyalty_points')
            ->paginate(50);

        return response()->json([
            'data' => $customers->map(fn ($c) => [
                'id'                => $c->id,
                'code'              => $c->customer_code,
                'name'              => $c->name,
                'phone'             => $c->phone,
                'loyalty_points'    => number_format($c->loyalty_points),
                'lifetime_earned'   => number_format($c->lifetime_earned_points),
                'lifetime_redeemed' => number_format($c->lifetime_redeemed_points),
                'total_purchase'    => Currency::format($c->total_purchase_amount),
                'member_badge'      => $c->is_member
                    ? '<span class="badge bg-success">Member</span>'
                    : '<span class="badge bg-secondary">Regular</span>',
                'member_since'      => DateFormatter::date($c->membership_started_at, '—'),
            ]),
            'meta' => [
                'total'        => $customers->total(),
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
            ],
        ]);
    }

    public function reportExcel(Request $request)
    {
        $type = $request->get('report_type', 'customers');

        if ($type === 'ledger') {
            $rows = LoyaltyPointLedger::query()
                ->with(['customer:id,name,phone', 'salesOrder:id,order_no', 'createdByUser:id,name'])
                ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($r) => [
                    DateFormatter::dateTime($r->created_at),
                    trim(($r->customer?->name ?? '') . ' (' . ($r->customer?->phone ?? '') . ')'),
                    ucfirst((string) $r->type),
                    (int) $r->points,
                    (float) $r->amount_value,
                    $r->salesOrder?->order_no ?? '',
                    $r->note,
                    $r->createdByUser?->name ?? '',
                ]);

            return $this->downloadCsv('loyalty-ledger-report-' . now()->format('Y-m-d-His'), [
                'Date', 'Customer', 'Type', 'Points', 'Value', 'Order', 'Note', 'By',
            ], $rows);
        }

        $rows = Customer::query()
            ->select(['id', 'name', 'phone', 'customer_code', 'loyalty_points', 'lifetime_earned_points', 'lifetime_redeemed_points', 'total_purchase_amount', 'is_member', 'membership_started_at'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sq) use ($request) {
                $sq->where('name', 'like', '%' . $request->get('search') . '%')
                   ->orWhere('phone', 'like', '%' . $request->get('search') . '%')
                   ->orWhere('customer_code', 'like', '%' . $request->get('search') . '%');
            }))
            ->when($request->boolean('members_only'), fn ($q) => $q->where('is_member', true))
            ->orderByDesc('loyalty_points')
            ->get()
            ->map(fn ($c) => [
                $c->customer_code,
                $c->name,
                $c->phone,
                (int) $c->loyalty_points,
                (int) $c->lifetime_earned_points,
                (int) $c->lifetime_redeemed_points,
                (float) $c->total_purchase_amount,
                $c->is_member ? 'Member' : 'Regular',
                DateFormatter::date($c->membership_started_at),
            ]);

        return $this->downloadCsv('loyalty-customer-report-' . now()->format('Y-m-d-His'), [
            'Code', 'Name', 'Phone', 'Points', 'Lifetime Earned', 'Lifetime Redeemed', 'Total Purchase', 'Status', 'Member Since',
        ], $rows);
    }

    private function typeBadge(string $type): string
    {
        return match ($type) {
            'earned'   => '<span class="badge bg-success">Earned</span>',
            'redeemed' => '<span class="badge bg-warning text-dark">Redeemed</span>',
            'adjusted' => '<span class="badge bg-info text-dark">Adjusted</span>',
            default    => '<span class="badge bg-secondary">' . ucfirst($type) . '</span>',
        };
    }
}
