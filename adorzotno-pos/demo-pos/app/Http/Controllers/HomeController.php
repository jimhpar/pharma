<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\ShipmentZone;
use App\Models\Sku;
use App\Models\StockBalance;
use App\Support\DateFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $selectedBranchId = $this->resolveReportBranchId($request);
        $availableBranches = $this->branchContext()->hasCrossBranchAccess($request->user())
            ? $this->branchContext()->accessibleBranches($request->user())
            : collect();
        $selectedBranch = $selectedBranchId ? Branch::query()->find($selectedBranchId) : null;
        $isConsolidated = $selectedBranchId === null && $this->branchContext()->hasCrossBranchAccess($request->user());

        $defaultStart = now()->startOfMonth();
        $defaultEnd = now()->endOfMonth();
        $periodStart = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()
            : $defaultStart->copy();
        $periodEndInclusive = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()
            : $defaultEnd->copy();

        if ($periodEndInclusive->lt($periodStart)) {
            $periodEndInclusive = $periodStart->copy()->endOfDay();
        }

        $periodEnd = $periodEndInclusive->copy()->addSecond();
        $filterStartDate = $periodStart->format('Y-m-d');
        $filterEndDate = $periodEndInclusive->format('Y-m-d');
        $periodLabel = $periodStart->isSameDay($periodEndInclusive)
            ? DateFormatter::date($periodStart)
            : DateFormatter::date($periodStart) . ' - ' . DateFormatter::date($periodEndInclusive);
        $todayStart = now()->startOfDay();
        $finalizedStatuses = ['confirmed', 'processing', 'packed', 'shipped', 'delivered', 'completed'];

        $salesQuery = SalesOrder::query()
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->whereIn('status', $finalizedStatuses);

        $periodSalesSummary = (clone $salesQuery)
            ->where('order_date', '>=', $periodStart)
            ->where('order_date', '<', $periodEnd)
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(grand_total), 0) as gross_sales, COALESCE(SUM(due_total), 0) as due_amount, COALESCE(AVG(grand_total), 0) as average_order_value')
            ->first();

        $todaySalesSummary = (clone $salesQuery)
            ->where('order_date', '>=', $todayStart)
            ->selectRaw('COUNT(*) as today_orders, COALESCE(SUM(grand_total), 0) as today_sales')
            ->first();

        $collectionsQuery = Payment::query()
            ->where('payment_direction', 'in')
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('branch_id', $branchId));

        $periodCollectionAmount = (float) (clone $collectionsQuery)
            ->where('payment_date', '>=', $periodStart)
            ->where('payment_date', '<', $periodEnd)
            ->sum('amount');
        $grossSales = (float) ($periodSalesSummary?->gross_sales ?? 0);
        $totalOrders = (int) ($periodSalesSummary?->total_orders ?? 0);
        $productSummary = Product::query()
            ->selectRaw("COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_products")
            ->selectRaw("COUNT(CASE WHEN product_type = 'variant_parent' THEN 1 END) as variant_products")
            ->first();
        $stockSummary = StockBalance::query()
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(available_quantity), 0) as units_on_hand')
            ->selectRaw('COUNT(CASE WHEN available_quantity <= reorder_level AND reorder_level > 0 THEN 1 END) as low_stock_count')
            ->selectRaw('COUNT(CASE WHEN available_quantity <= 0 THEN 1 END) as out_of_stock_count')
            ->first();
        $pendingOrderSummary = SalesOrder::query()
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->where('status', 'draft')
            ->selectRaw("COUNT(CASE WHEN internal_note LIKE '%Sale Type: Quotation%' THEN 1 END) as open_quote_count")
            ->selectRaw("COUNT(CASE WHEN internal_note LIKE '%Sale Type: Draft%' THEN 1 END) as draft_count")
            ->selectRaw("COUNT(CASE WHEN internal_note LIKE '%Sale Type: Suspend%' THEN 1 END) as suspend_count")
            ->first();
        $stockMovementSummary = DB::table('inventory_transactions')
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->where('occurred_at', '>=', $periodStart)
            ->where('occurred_at', '<', $periodEnd)
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) as stock_in_this_month')
            ->selectRaw('COALESCE(ABS(SUM(CASE WHEN quantity < 0 THEN quantity ELSE 0 END)), 0) as stock_out_this_month')
            ->first();

        $dashboard = [
            'period_label' => $isConsolidated ? 'All Branches · ' . $periodLabel : (($selectedBranch?->name ?? 'Current Branch') . ' · ' . $periodLabel),
            'gross_sales' => $grossSales,
            'collected_amount' => $periodCollectionAmount,
            'today_collections' => (float) (clone $collectionsQuery)->where('payment_date', '>=', $todayStart)->sum('amount'),
            'due_amount' => (float) ($periodSalesSummary?->due_amount ?? 0),
            'today_sales' => (float) ($todaySalesSummary?->today_sales ?? 0),
            'today_orders' => (int) ($todaySalesSummary?->today_orders ?? 0),
            'total_orders' => $totalOrders,
            'average_order_value' => (float) ($periodSalesSummary?->average_order_value ?? 0),
            'collection_rate' => (float) ($grossSales > 0 ? ($periodCollectionAmount / $grossSales) * 100 : 0),
            'total_customers' => $this->customerCount($selectedBranchId),
            'new_customers_this_month' => $this->customerCount($selectedBranchId, $periodStart, $periodEnd),
            'active_products' => (int) ($productSummary?->active_products ?? 0),
            'variant_products' => (int) ($productSummary?->variant_products ?? 0),
            'total_skus' => (int) Sku::query()->count(),
            'active_shipment_zones' => (int) ShipmentZone::query()->where('status', 'Active')->count(),
            'units_on_hand' => (int) ($stockSummary?->units_on_hand ?? 0),
            'stock_value' => (float) StockBalance::query()
                ->join('inventory_batches', 'inventory_batches.id', '=', 'stock_balances.batch_id')
                ->when($selectedBranchId, fn ($query, $branchId) => $query->where('stock_balances.branch_id', $branchId))
                ->selectRaw('COALESCE(SUM(stock_balances.available_quantity * inventory_batches.purchase_price), 0) as stock_value')
                ->value('stock_value'),
            'low_stock_count' => (int) ($stockSummary?->low_stock_count ?? 0),
            'out_of_stock_count' => (int) ($stockSummary?->out_of_stock_count ?? 0),
            'open_quote_count' => (int) ($pendingOrderSummary?->open_quote_count ?? 0),
            'draft_count' => (int) ($pendingOrderSummary?->draft_count ?? 0),
            'suspend_count' => (int) ($pendingOrderSummary?->suspend_count ?? 0),
            'stock_in_this_month' => (int) ($stockMovementSummary?->stock_in_this_month ?? 0),
            'stock_out_this_month' => (int) ($stockMovementSummary?->stock_out_this_month ?? 0),
        ];

        $topProducts = SalesOrderItem::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('product_skus', 'product_skus.id', '=', 'sales_order_items.sku_id')
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('sales_orders.branch_id', $branchId))
            ->whereIn('sales_orders.status', $finalizedStatuses)
            ->where('sales_orders.order_date', '>=', $periodStart)
            ->where('sales_orders.order_date', '<', $periodEnd)
            ->selectRaw('products.id, products.name, COALESCE(SUM(sales_order_items.quantity), 0) as units_sold, COALESCE(SUM(sales_order_items.line_total), 0) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units_sold')
            ->limit(8)
            ->get();

        $shipmentZonePerformance = $this->buildShipmentZonePerformance($selectedBranchId, $periodStart, $periodEnd, $finalizedStatuses);

        $lowStockItems = StockBalance::query()
            ->join('product_skus', 'product_skus.id', '=', 'stock_balances.sku_id')
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('stock_balances.branch_id', $branchId))
            ->whereColumn('stock_balances.available_quantity', '<=', 'stock_balances.reorder_level')
            ->where('stock_balances.reorder_level', '>', 0)
            ->selectRaw('products.name as product_name, product_skus.sku_code as product_code, stock_balances.available_quantity as current_stock, stock_balances.reorder_level as alert_level')
            ->orderBy('stock_balances.available_quantity')
            ->limit(8)
            ->get();

        $recentOrders = SalesOrder::query()
            ->leftJoin('customers', 'customers.id', '=', 'sales_orders.customer_id')
            ->when($selectedBranchId, fn ($query, $branchId) => $query->where('sales_orders.branch_id', $branchId))
            ->where('sales_orders.order_date', '>=', $periodStart)
            ->where('sales_orders.order_date', '<', $periodEnd)
            ->selectRaw('sales_orders.id, sales_orders.created_at, sales_orders.grand_total, sales_orders.paid_total as paid_amount, sales_orders.due_total, sales_orders.payment_status as payment_last_status, sales_orders.status as order_last_status, sales_orders.internal_note, COALESCE(customers.name, "Walk-in Customer") as customer_name')
            ->orderByDesc('sales_orders.id')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                $order->sale_type = str_contains((string) $order->internal_note, 'Sale Type:')
                    ? trim((string) preg_replace('/.*Sale Type:\s*([^|]+).*/', '$1', (string) $order->internal_note))
                    : 'Sale';

                return $order;
            });

        return view('dashboard', compact(
            'dashboard',
            'topProducts',
            'shipmentZonePerformance',
            'lowStockItems',
            'recentOrders',
            'availableBranches',
            'selectedBranch',
            'isConsolidated',
            'filterStartDate',
            'filterEndDate',
            'periodLabel'
        ));
    }

    private function customerCount(?int $branchId, ?Carbon $createdSince = null, ?Carbon $createdBefore = null): int
    {
        return (int) Customer::query()
            ->when($createdSince, fn ($query, Carbon $date) => $query->where('customers.created_at', '>=', $date))
            ->when($createdBefore, fn ($query, Carbon $date) => $query->where('customers.created_at', '<', $date))
            ->when($branchId, function ($query, int $selectedBranchId) {
                $query->whereExists(function ($subQuery) use ($selectedBranchId) {
                    $subQuery
                        ->selectRaw('1')
                        ->from('sales_orders')
                        ->whereColumn('sales_orders.customer_id', 'customers.id')
                        ->where('sales_orders.branch_id', $selectedBranchId);
                });
            })
            ->count();
    }

    private function buildShipmentZonePerformance(?int $branchId, Carbon $periodStart, Carbon $periodEnd, array $finalizedStatuses): Collection
    {
        $activeZones = ShipmentZone::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->pluck('name');

        if ($activeZones->isEmpty()) {
            return collect();
        }

        $zoneRows = SalesOrder::query()
            ->when($branchId, fn ($query, $selectedBranchId) => $query->where('branch_id', $selectedBranchId))
            ->whereIn('status', $finalizedStatuses)
            ->where('order_date', '>=', $periodStart)
            ->where('order_date', '<', $periodEnd)
            ->where('internal_note', 'like', '%Shipment Zone:%')
            ->selectRaw("TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(internal_note, 'Shipment Zone:', -1), '|', 1)) as zone_name")
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(grand_total), 0) as revenue')
            ->groupBy('zone_name')
            ->get()
            ->keyBy('zone_name');

        return $activeZones
            ->map(function (string $zoneName) use ($zoneRows) {
                $zoneOrders = $zoneRows->get($zoneName);

                return (object) [
                    'zone_name' => $zoneName,
                    'order_count' => (int) ($zoneOrders?->order_count ?? 0),
                    'revenue' => (float) ($zoneOrders?->revenue ?? 0),
                ];
            })
            ->filter(fn ($zone) => $zone->order_count > 0)
            ->values();
    }
}
