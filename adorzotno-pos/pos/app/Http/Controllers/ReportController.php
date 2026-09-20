<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Brand;
use App\Models\InventoryCarton;
use App\Models\InventoryBox;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\PurchaseOrder;
use App\Models\SalesCommissionPlan;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesStaffProfile;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ─── Product Sales Report ────────────────────────────────────────────────

    public function productSalesShow(Request $request)
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();

        $categories    = Category::orderBy('name')->get();
        $brands        = Brand::orderBy('name')->get();
        $salesChannels = SalesOrder::channelOptions();

        $monthItems = SalesOrderItem::query()
            ->with(['sku'])
            ->whereHas('salesOrder', function ($q) use ($request) {
                $this->scopeReportBranch($q, $request);

                $q->whereIn('status', SalesOrder::REPORTABLE_SALES_STATUSES)
                    ->whereMonth('order_date', now()->month)
                    ->whereYear('order_date', now()->year);
            })
            ->get();

        $thisMonth = [
            'revenue' => $monthItems->sum('line_total'),
            'qty'     => $monthItems->sum('quantity'),
            'cost'    => $monthItems->sum(fn ($i) => $i->cost_price * $i->quantity),
            'profit'  => $monthItems->sum('line_total') - $monthItems->sum(fn ($i) => $i->cost_price * $i->quantity),
        ];

        return view('report.product-sales-report', compact('branches', 'categories', 'brands', 'salesChannels', 'thisMonth'));
    }

    public function productSalesList(Request $request)
    {
        $items = $this->productSalesQuery($request);
        $data  = $items->get();

        $totalRevenue = $data->sum('line_total');
        $totalCost    = $data->sum(fn ($i) => $i->cost_price * $i->quantity);
        $totalProfit  = $totalRevenue - $totalCost;
        $totalQty     = $data->sum('quantity');

        return DataTables()->of($items)
            ->addColumn('product_name',  fn (SalesOrderItem $i) => e($i->sku?->product?->name ?? 'N/A'))
            ->addColumn('sku_code',      fn (SalesOrderItem $i) => $i->sku?->sku_code ?? 'N/A')
            ->addColumn('variant',       fn (SalesOrderItem $i) => $i->sku?->variant_name ?: 'Single')
            ->addColumn('category',      fn (SalesOrderItem $i) => $i->sku?->product?->category?->name ?? 'N/A')
            ->addColumn('brand',         fn (SalesOrderItem $i) => $i->sku?->product?->brand?->name ?? 'N/A')
            ->addColumn('branch',        fn (SalesOrderItem $i) => $i->salesOrder?->branch?->name ?? 'N/A')
            ->addColumn('sales_channel', fn (SalesOrderItem $i) => SalesOrder::canonicalChannel($i->salesOrder?->sales_channel) ?? 'N/A')
            ->addColumn('qty',           fn (SalesOrderItem $i) => (int) $i->quantity)
            ->addColumn('unit_price',    fn (SalesOrderItem $i) => Currency::format($i->unit_price))
            ->addColumn('discount',      fn (SalesOrderItem $i) => Currency::format($i->discount_amount))
            ->addColumn('total',         fn (SalesOrderItem $i) => Currency::format($i->line_total))
            ->addColumn('cost',          fn (SalesOrderItem $i) => Currency::format($i->cost_price * $i->quantity))
            ->addColumn('profit',        function (SalesOrderItem $i) {
                $profit = $i->line_total - ($i->cost_price * $i->quantity);
                return Currency::format($profit);
            })
            ->setRowAttr(['align' => 'center'])
            ->with([
                'totalRevenue' => $totalRevenue,
                'totalCost'    => $totalCost,
                'totalProfit'  => $totalProfit,
                'totalQty'     => $totalQty,
            ])
            ->make(true);
    }

    public function productSalesExcel(Request $request)
    {
        $rows = $this->productSalesQuery($request)->get()->map(fn (SalesOrderItem $i) => [
            $i->sku?->product?->name ?? 'N/A',
            $i->sku?->sku_code ?? 'N/A',
            $i->sku?->variant_name ?: 'Single',
            $i->sku?->product?->category?->name ?? 'N/A',
            $i->sku?->product?->brand?->name ?? 'N/A',
            $i->salesOrder?->branch?->name ?? 'N/A',
            $i->salesOrder?->sales_channel_label ?? 'N/A',
            (int) $i->quantity,
            (float) $i->unit_price,
            (float) $i->discount_amount,
            (float) $i->line_total,
            (float) $i->cost_price * (int) $i->quantity,
            (float) $i->line_total - ((float) $i->cost_price * (int) $i->quantity),
        ]);

        return $this->downloadCsv('product-sales-report-' . now()->format('Y-m-d-His'), [
            'Product', 'SKU', 'Variant', 'Category', 'Brand', 'Branch', 'Channel', 'Qty Sold', 'Unit Price', 'Discount', 'Total', 'Cost', 'Profit',
        ], $rows);
    }

    // ─── Purchase Report ─────────────────────────────────────────────────────

    public function purchaseShow()
    {
        $branches  = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();

        $suppliers = Supplier::orderBy('name')->get();

        $monthOrders = PurchaseOrder::query()
            ->whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->get();

        $thisMonth = [
            'count'   => $monthOrders->count(),
            'total'   => $monthOrders->sum('grand_total'),
            'paid'    => $monthOrders->sum('paid_total'),
            'due'     => $monthOrders->sum('due_total'),
        ];

        return view('report.purchase-report', compact('branches', 'suppliers', 'thisMonth'));
    }

    public function purchaseList(Request $request)
    {
        $orders = $this->purchaseReportQuery($request);

        $data = $orders->get();

        return DataTables()->of($orders)
            ->addColumn('branch_name',   fn (PurchaseOrder $o) => $o->branch?->name ?? 'N/A')
            ->addColumn('supplier_name', fn (PurchaseOrder $o) => $o->supplier?->name ?? 'N/A')
            ->addColumn('created_by',    fn (PurchaseOrder $o) => $o->createdBy?->name ?? 'N/A')
            ->addColumn('status_badge',  function (PurchaseOrder $o) {
                $map = [
                    'draft'    => ['#e2e8f0','#475569','Draft'],
                    'ordered'  => ['#dbeafe','#1e40af','Ordered'],
                    'partial'  => ['#fef3c7','#92400e','Partial'],
                    'received' => ['#d1fae5','#065f46','Received'],
                    'canceled' => ['#fee2e2','#991b1b','Canceled'],
                ];
                [$bg,$color,$label] = $map[$o->status] ?? ['#f1f5f9','#64748b',ucfirst($o->status)];
                return '<span style="background:'.$bg.';color:'.$color.';font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px;white-space:nowrap">'.$label.'</span>';
            })
            ->editColumn('grand_total',  fn (PurchaseOrder $o) => Currency::format($o->grand_total))
            ->editColumn('paid_total',   fn (PurchaseOrder $o) => Currency::format($o->paid_total))
            ->editColumn('due_total',    fn (PurchaseOrder $o) => Currency::format($o->due_total))
            ->editColumn('purchase_date', fn (PurchaseOrder $o) => DateFormatter::date($o->purchase_date))
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge'])
            ->with([
                'grandTotalSum' => $data->sum('grand_total'),
                'paidTotalSum'  => $data->sum('paid_total'),
                'dueTotalSum'   => $data->sum('due_total'),
            ])
            ->make(true);
    }

    public function purchaseExcel(Request $request)
    {
        $rows = $this->purchaseReportQuery($request)->get()->map(fn (PurchaseOrder $o) => [
            $o->purchase_no,
            DateFormatter::date($o->purchase_date),
            $o->branch?->name ?? 'N/A',
            $o->supplier?->name ?? 'N/A',
            (float) $o->grand_total,
            (float) $o->paid_total,
            (float) $o->due_total,
            ucfirst((string) $o->status),
            $o->createdBy?->name ?? 'N/A',
        ]);

        return $this->downloadCsv('purchase-report-' . now()->format('Y-m-d-His'), [
            'Purchase No', 'Date', 'Branch', 'Supplier', 'Grand Total', 'Paid', 'Due', 'Status', 'Created By',
        ], $rows);
    }

    // ─── Supplier Due Report ─────────────────────────────────────────────────

    public function supplierDueShow()
    {
        $stats = [
            'total'    => Supplier::count(),
            'has_due'  => Supplier::where('current_due', '>', 0)->count(),
            'no_due'   => Supplier::where('current_due', '<=', 0)->count(),
            'total_due'=> Supplier::sum('current_due'),
        ];

        return view('report.supplier-due-report', compact('stats'));
    }

    public function supplierDueList(Request $request)
    {
        $suppliers = $this->supplierDueQuery($request);

        return DataTables()->of($suppliers)
            ->addColumn('status_badge', function (Supplier $s) {
                [$bg,$color,$label] = $s->status === 'active'
                    ? ['#d1fae5','#065f46','Active']
                    : ['#fee2e2','#991b1b','Inactive'];
                return '<span style="background:'.$bg.';color:'.$color.';font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">'.$label.'</span>';
            })
            ->addColumn('due_badge', function (Supplier $s) {
                return $s->current_due > 0
                    ? '<span style="font-weight:800;color:#dc2626;white-space:nowrap">' . Currency::format($s->current_due) . '</span>'
                    : '<span style="font-weight:700;color:#15803d;white-space:nowrap">' . Currency::format($s->current_due) . '</span>';
            })
            ->editColumn('opening_balance', fn (Supplier $s) => Currency::format($s->opening_balance))
            ->editColumn('credit_limit',    fn (Supplier $s) => Currency::format($s->credit_limit))
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge', 'due_badge'])
            ->make(true);
    }

    public function supplierDueExcel(Request $request)
    {
        $rows = $this->supplierDueQuery($request)->get()->map(fn (Supplier $s) => [
            $s->supplier_code,
            $s->name,
            $s->phone,
            $s->email,
            (float) $s->opening_balance,
            (float) $s->credit_limit,
            (float) $s->current_due,
            $s->payment_terms,
            ucfirst((string) $s->status),
        ]);

        return $this->downloadCsv('supplier-due-report-' . now()->format('Y-m-d-His'), [
            'Supplier Code', 'Name', 'Phone', 'Email', 'Opening Balance', 'Credit Limit', 'Current Due', 'Payment Terms', 'Status',
        ], $rows);
    }

    // ─── Customer Due Report ──────────────────────────────────────────────────

    public function customerDueShow()
    {
        $salesChannels = SalesOrder::channelOptions();

        $stats = [
            'total'          => Customer::count(),
            'has_due'        => Customer::where('current_due', '>', 0)->count(),
            'no_due'         => Customer::where('current_due', '<=', 0)->count(),
            'total_due'      => Customer::sum('current_due'),
            'total_purchase' => Customer::sum('total_purchase_amount'),
        ];

        return view('report.customer-due-report', compact('salesChannels', 'stats'));
    }

    public function customerDueList(Request $request)
    {
        $customers = $this->customerDueQuery($request);

        return DataTables()->of($customers)
            ->addColumn('profile_url', fn (Customer $c) => route('customer.statement', $c->id))
            ->addColumn('status_badge', function (Customer $c) {
                [$bg,$color,$label] = ($c->status ?? 'active') === 'active'
                    ? ['#d1fae5','#065f46','Active']
                    : ['#fee2e2','#991b1b','Inactive'];
                return '<span style="background:'.$bg.';color:'.$color.';font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">'.$label.'</span>';
            })
            ->addColumn('due_badge', function (Customer $c) {
                return $c->current_due > 0
                    ? '<span style="font-weight:800;color:#dc2626;white-space:nowrap">' . Currency::format($c->current_due) . '</span>'
                    : '<span style="font-weight:700;color:#15803d;white-space:nowrap">' . Currency::format($c->current_due) . '</span>';
            })
            ->editColumn('total_purchase_amount', fn (Customer $c) => Currency::format($c->total_purchase_amount))
            ->editColumn('credit_limit',          fn (Customer $c) => Currency::format($c->credit_limit))
            ->addColumn('sales_channels', fn (Customer $c) => $this->customerSalesChannelsLabel($c))
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge', 'due_badge'])
            ->make(true);
    }

    public function customerDueExcel(Request $request)
    {
        $rows = $this->customerDueQuery($request)->get()->map(fn (Customer $c) => [
            $c->customer_code,
            $c->name,
            $c->phone,
            $c->email,
            $this->customerSalesChannelsLabel($c),
            (float) $c->total_purchase_amount,
            (float) $c->credit_limit,
            (float) $c->current_due,
            (int) $c->loyalty_points,
            ucfirst((string) ($c->status ?? 'active')),
        ]);

        return $this->downloadCsv('customer-due-report-' . now()->format('Y-m-d-His'), [
            'Code', 'Name', 'Phone', 'Email', 'Channel', 'Total Purchase', 'Credit Limit', 'Current Due', 'Loyalty Points', 'Status',
        ], $rows);
    }

    // ─── Low Stock Alert Report ───────────────────────────────────────────────

    public function lowStockShow()
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();

        $warehouses = Warehouse::orderBy('name')->get();

        $stats = [
            'out_of_stock' => StockBalance::where('available_quantity', '<=', 0)->whereColumn('available_quantity', '<=', 'reorder_level')->count(),
            'low_stock'    => StockBalance::where('available_quantity', '>', 0)->whereColumn('available_quantity', '<=', 'reorder_level')->count(),
        ];
        $stats['total'] = $stats['out_of_stock'] + $stats['low_stock'];

        return view('report.low-stock-report', compact('branches', 'warehouses', 'stats'));
    }

    public function lowStockList(Request $request)
    {
        $stocks = $this->lowStockQuery($request);

        return DataTables()->of($stocks)
            ->addColumn('branch',       fn (StockBalance $s) => $s->branch?->name ?? 'N/A')
            ->addColumn('warehouse',    fn (StockBalance $s) => $s->warehouse?->name ?? 'N/A')
            ->addColumn('sku_code',     fn (StockBalance $s) => $s->sku?->sku_code ?? 'N/A')
            ->addColumn('product_name', fn (StockBalance $s) => $s->sku?->product?->name ?? 'N/A')
            ->addColumn('variant',      fn (StockBalance $s) => $s->sku?->variant_name ?: 'Single')
            ->addColumn('category',     fn (StockBalance $s) => $s->sku?->product?->category?->name ?? 'N/A')
            ->addColumn('brand',        fn (StockBalance $s) => $s->sku?->product?->brand?->name ?? 'N/A')
            ->addColumn('available',    fn (StockBalance $s) => (int) $s->available_quantity)
            ->addColumn('reorder',      fn (StockBalance $s) => (int) $s->reorder_level)
            ->addColumn('status_badge', function (StockBalance $s) {
                if ($s->available_quantity <= 0) {
                    return '<span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">Out of Stock</span>';
                }
                return '<span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">Low Stock</span>';
            })
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function lowStockExcel(Request $request)
    {
        $rows = $this->lowStockQuery($request)->get()->map(fn (StockBalance $s) => [
            $s->sku?->sku_code ?? 'N/A',
            $s->sku?->product?->name ?? 'N/A',
            $s->sku?->variant_name ?: 'Single',
            $s->sku?->product?->category?->name ?? 'N/A',
            $s->sku?->product?->brand?->name ?? 'N/A',
            $s->branch?->name ?? 'N/A',
            $s->warehouse?->name ?? 'N/A',
            (int) $s->available_quantity,
            (int) $s->reorder_level,
            $s->available_quantity <= 0 ? 'Out of Stock' : 'Low Stock',
        ]);

        return $this->downloadCsv('low-stock-report-' . now()->format('Y-m-d-His'), [
            'SKU Code', 'Product', 'Variant', 'Category', 'Brand', 'Branch', 'Warehouse', 'Available', 'Reorder Level', 'Status',
        ], $rows);
    }

    private function productSalesQuery(Request $request)
    {
        return SalesOrderItem::query()
            ->with(['sku.product.category', 'sku.product.brand', 'salesOrder.branch'])
            ->whereHas('salesOrder', function ($q) use ($request) {
                $this->scopeReportBranch($q, $request);

                $q->whereIn('status', SalesOrder::REPORTABLE_SALES_STATUSES);
                if ($request->filled('startDate')) {
                    $q->whereDate('order_date', '>=', $request->startDate);
                }
                if ($request->filled('endDate')) {
                    $q->whereDate('order_date', '<=', $request->endDate);
                }
                if ($request->filled('salesChannel')) {
                    $q->whereIn('sales_channel', SalesOrder::channelFilterValues($request->salesChannel));
                }
            })
            ->when($request->filled('category_id'), fn ($q) =>
                $q->whereHas('sku.product', fn ($q2) =>
                    $q2->where('category_id', $request->category_id)
                )
            )
            ->when($request->filled('brand_id'), fn ($q) =>
                $q->whereHas('sku.product', fn ($q2) =>
                    $q2->where('brand_id', $request->brand_id)
                )
            );
    }

    private function purchaseReportQuery(Request $request)
    {
        $branchId = $this->resolveReportBranchId($request);

        return PurchaseOrder::query()
            ->with(['branch', 'supplier', 'createdBy'])
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('status'),      fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('startDate'),   fn ($q) => $q->whereDate('purchase_date', '>=', $request->startDate))
            ->when($request->filled('endDate'),     fn ($q) => $q->whereDate('purchase_date', '<=', $request->endDate))
            ->orderByDesc('purchase_date');
    }

    private function supplierDueQuery(Request $request)
    {
        return Supplier::query()
            ->when($request->filled('search_name'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search_name . '%')
            )
            ->when($request->filled('due_filter'), function ($q) use ($request) {
                if ($request->due_filter === 'has_due') {
                    $q->where('current_due', '>', 0);
                } elseif ($request->due_filter === 'no_due') {
                    $q->where('current_due', '<=', 0);
                }
            })
            ->orderByDesc('current_due');
    }

    private function customerDueQuery(Request $request)
    {
        return Customer::query()
            ->with(['salesOrders:id,customer_id,sales_channel'])
            ->when($request->filled('search_name'), fn ($q) =>
                $q->where(function ($subQuery) use ($request) {
                    $subQuery
                        ->where('name', 'like', '%' . $request->search_name . '%')
                        ->orWhere('phone', 'like', '%' . $request->search_name . '%');
                })
            )
            ->when($request->filled('due_filter'), function ($q) use ($request) {
                if ($request->due_filter === 'has_due') {
                    $q->where('current_due', '>', 0);
                } elseif ($request->due_filter === 'no_due') {
                    $q->where('current_due', '<=', 0);
                }
            })
            ->when($request->filled('salesChannel'), function ($q) use ($request) {
                $q->whereHas('salesOrders', fn ($salesQuery) =>
                    $salesQuery->where('sales_channel', $request->salesChannel)
                );
            })
            ->orderByDesc('current_due');
    }

    private function customerSalesChannelsLabel(Customer $customer): string
    {
        $labels = $customer->salesOrders
            ->pluck('sales_channel')
            ->filter()
            ->unique()
            ->map(fn ($channel) => SalesOrder::channelOptions()[$channel] ?? ucfirst((string) $channel))
            ->values();

        return $labels->isEmpty() ? 'N/A' : $labels->implode(', ');
    }

    private function lowStockQuery(Request $request)
    {
        $branchId = $this->resolveReportBranchId($request);

        return StockBalance::query()
            ->with(['branch', 'warehouse', 'sku.product.category', 'sku.product.brand'])
            ->whereColumn('available_quantity', '<=', 'reorder_level')
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('stock_filter'), function ($q) use ($request) {
                if ($request->stock_filter === 'out_of_stock') {
                    $q->where('available_quantity', '<=', 0);
                } elseif ($request->stock_filter === 'low_stock') {
                    $q->where('available_quantity', '>', 0);
                }
            })
            ->orderBy('available_quantity');
    }

    // ─── Carton / Box Stock Report ───────────────────────────────────────────

    public function cartonBoxShow()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $stats = [
            'cartons'      => InventoryCarton::count(),
            'boxes_total'  => InventoryBox::count(),
            'boxes_open'   => InventoryBox::where('status', 'open')->count(),
            'boxes_sealed' => InventoryBox::where('status', 'sealed')->count(),
            'boxes_empty'  => InventoryBox::where('status', 'empty')->count(),
            'units_available' => InventoryBox::whereIn('status', ['open', 'sealed'])->sum('units_available'),
        ];

        return view('report.carton-box-report', compact('warehouses', 'categories', 'stats'));
    }

    public function cartonBoxList(Request $request)
    {
        $query = InventoryCarton::query()
            ->with(['sku.product.category', 'warehouse', 'boxes'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('category_id'),  fn ($q) =>
                $q->whereHas('sku.product', fn ($p) => $p->where('category_id', $request->category_id))
            )
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('available_units', '>', 0);
                } elseif ($request->status === 'empty') {
                    $q->where('available_units', 0);
                }
            })
            ->orderByDesc('id');

        return DataTables()->of($query)
            ->addColumn('carton_code',    fn (InventoryCarton $c) => $c->carton_code)
            ->addColumn('product',        fn (InventoryCarton $c) => $c->sku?->product?->name ?? 'N/A')
            ->addColumn('sku_code',       fn (InventoryCarton $c) => $c->sku?->sku_code ?? 'N/A')
            ->addColumn('category',       fn (InventoryCarton $c) => $c->sku?->product?->category?->name ?? 'N/A')
            ->addColumn('warehouse',      fn (InventoryCarton $c) => $c->warehouse?->name ?? 'N/A')
            ->addColumn('config',         fn (InventoryCarton $c) => $c->boxes_per_carton . ' × ' . $c->units_per_box)
            ->addColumn('total_units',    fn (InventoryCarton $c) => (int) $c->total_units)
            ->addColumn('available_units',fn (InventoryCarton $c) => (int) $c->available_units)
            ->addColumn('sold_units',     fn (InventoryCarton $c) => $c->total_units - $c->available_units)
            ->addColumn('unit_cost',      fn (InventoryCarton $c) => $c->unit_cost !== null ? Currency::format($c->unit_cost) : '—')
            ->addColumn('batch_no_label', fn (InventoryCarton $c) => $c->batch_no_label ?? '—')
            ->addColumn('expiry_date',    fn (InventoryCarton $c) => $c->expiry_date?->format('Y-m-d') ?? '—')
            ->addColumn('boxes_sealed',   fn (InventoryCarton $c) => $c->boxes->where('status', 'sealed')->count())
            ->addColumn('boxes_open',     fn (InventoryCarton $c) => $c->boxes->where('status', 'open')->count())
            ->addColumn('boxes_empty',    fn (InventoryCarton $c) => $c->boxes->where('status', 'empty')->count())
            ->addColumn('received_at',    fn (InventoryCarton $c) => $c->received_at?->format('Y-m-d') ?? 'N/A')
            ->addColumn('status_badge',   function (InventoryCarton $c) {
                if ($c->available_units <= 0) {
                    return '<span style="background:#f3f4f6;color:#6b7280;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:20px">Empty</span>';
                }
                if ($c->available_units < $c->total_units) {
                    return '<span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:20px">Partial</span>';
                }
                return '<span style="background:#dbeafe;color:#1e40af;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:20px">Full</span>';
            })
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function cartonBoxExcel(Request $request)
    {
        $rows = InventoryCarton::query()
            ->with(['sku.product.category', 'warehouse', 'boxes'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('category_id'),  fn ($q) =>
                $q->whereHas('sku.product', fn ($p) => $p->where('category_id', $request->category_id))
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryCarton $c) => [
                $c->carton_code,
                $c->sku?->product?->name ?? 'N/A',
                $c->sku?->sku_code ?? 'N/A',
                $c->sku?->product?->category?->name ?? 'N/A',
                $c->warehouse?->name ?? 'N/A',
                $c->boxes_per_carton,
                $c->units_per_box,
                $c->total_units,
                $c->available_units,
                $c->total_units - $c->available_units,
                $c->unit_cost !== null ? (float) $c->unit_cost : '',
                $c->batch_no_label ?? '',
                $c->expiry_date?->format('Y-m-d') ?? '',
                $c->boxes->where('status', 'sealed')->count(),
                $c->boxes->where('status', 'open')->count(),
                $c->boxes->where('status', 'empty')->count(),
                $c->received_at?->format('Y-m-d') ?? 'N/A',
            ]);

        return $this->downloadCsv('carton-box-report-' . now()->format('Y-m-d-His'), [
            'Carton Code', 'Product', 'SKU', 'Category', 'Warehouse',
            'Boxes/Carton', 'Units/Box', 'Total Units', 'Available Units', 'Sold Units',
            'Unit Cost', 'Batch No', 'Expiry Date',
            'Sealed Boxes', 'Open Boxes', 'Empty Boxes', 'Received Date',
        ], $rows);
    }

    // ─── Branch Stock Summary Report ─────────────────────────────────────────

    public function branchStockShow()
    {
        $branches   = Branch::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // Grand totals
        $totals = StockBalance::query()
            ->select([
                DB::raw('COUNT(DISTINCT sku_id) as sku_count'),
                DB::raw('SUM(available_quantity) as total_qty'),
                $this->stockValueExpr(),
            ])
            ->where('available_quantity', '>', 0)
            ->first();

        $stats = [
            'branches'    => Branch::has('stockBalances')->count(),
            'sku_count'   => (int) ($totals->sku_count ?? 0),
            'total_qty'   => (int) ($totals->total_qty ?? 0),
            'total_value' => (float) ($totals->total_value ?? 0),
        ];

        return view('report.branch-stock-report', compact('branches', 'categories', 'warehouses', 'stats'));
    }

    public function branchStockList(Request $request)
    {
        $rows = StockBalance::query()
            ->select([
                'branch_id',
                'warehouse_id',
                DB::raw('COUNT(DISTINCT sku_id) as sku_count'),
                DB::raw('SUM(available_quantity) as total_qty'),
                $this->stockValueExpr(),
            ])
            ->where('available_quantity', '>', 0)
            ->with(['branch', 'warehouse'])
            ->when($request->filled('branch_id'),    fn ($q) => $q->where('branch_id',    $request->branch_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('category_id'),  fn ($q) =>
                $q->whereHas('sku.product', fn ($p) => $p->where('category_id', $request->category_id))
            )
            ->groupBy('branch_id', 'warehouse_id')
            ->orderBy('branch_id')
            ->orderBy('warehouse_id')
            ->get();

        return DataTables()->of($rows)
            ->addColumn('branch_name',    fn ($r) => $r->branch?->name    ?? 'N/A')
            ->addColumn('warehouse_name', fn ($r) => $r->warehouse?->name ?? 'N/A')
            ->addColumn('sku_count',      fn ($r) => (int) $r->sku_count)
            ->addColumn('total_qty',      fn ($r) => (int) $r->total_qty)
            ->addColumn('total_value',    fn ($r) => Currency::format((float) $r->total_value))
            ->addColumn('raw_value',      fn ($r) => (float) $r->total_value)
            ->setRowAttr(['align' => 'center'])
            ->make(true);
    }

    public function branchStockExcel(Request $request)
    {
        $rows = StockBalance::query()
            ->select([
                'branch_id',
                'warehouse_id',
                DB::raw('COUNT(DISTINCT sku_id) as sku_count'),
                DB::raw('SUM(available_quantity) as total_qty'),
                $this->stockValueExpr(),
            ])
            ->where('available_quantity', '>', 0)
            ->with(['branch', 'warehouse'])
            ->when($request->filled('branch_id'),    fn ($q) => $q->where('branch_id',    $request->branch_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('category_id'),  fn ($q) =>
                $q->whereHas('sku.product', fn ($p) => $p->where('category_id', $request->category_id))
            )
            ->groupBy('branch_id', 'warehouse_id')
            ->orderBy('branch_id')
            ->orderBy('warehouse_id')
            ->get()
            ->map(fn ($r) => [
                $r->branch?->name    ?? 'N/A',
                $r->warehouse?->name ?? 'N/A',
                (int) $r->sku_count,
                (int) $r->total_qty,
                round((float) $r->total_value, 2),
            ]);

        return $this->downloadCsv('branch-stock-' . now()->format('Y-m-d-His'), [
            'Branch', 'Warehouse', 'SKU Count', 'Total Qty', 'Stock Value (৳)',
        ], $rows);
    }

    // ─── Expiry Report ────────────────────────────────────────────────────────

    public function expiryShow()
    {
        $today = now();

        $base = InventoryBatch::whereNotNull('expiry_date')->where('available_quantity', '>', 0);

        $stats = [
            'expired'  => (clone $base)->whereDate('expiry_date', '<',  $today)->count(),
            'critical' => (clone $base)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $today->copy()->addDays(30))->count(),
            'warning'  => (clone $base)->whereDate('expiry_date', '>',  $today->copy()->addDays(30))->whereDate('expiry_date', '<=', $today->copy()->addDays(90))->count(),
        ];
        $stats['total'] = $stats['expired'] + $stats['critical'] + $stats['warning'];

        $categories = Category::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('report.expiry-report', compact('stats', 'categories', 'warehouses'));
    }

    public function expiryList(Request $request)
    {
        $batches = $this->expiryQuery($request);

        return DataTables()->of($batches)
            ->addColumn('batch_no',       fn (InventoryBatch $b) => $b->batch_no ?? 'N/A')
            ->addColumn('sku_code',       fn (InventoryBatch $b) => $b->sku?->sku_code ?? 'N/A')
            ->addColumn('product_name',   fn (InventoryBatch $b) => $b->sku?->product?->name ?? 'N/A')
            ->addColumn('variant',        fn (InventoryBatch $b) => $b->sku?->variant_name ?: 'Single')
            ->addColumn('category',       fn (InventoryBatch $b) => $b->sku?->product?->category?->name ?? 'N/A')
            ->addColumn('warehouse',      fn (InventoryBatch $b) => $b->warehouse?->name ?? 'N/A')
            ->addColumn('supplier',       fn (InventoryBatch $b) => $b->supplier?->name ?? 'N/A')
            ->addColumn('available_qty',  fn (InventoryBatch $b) => (int) $b->available_quantity)
            ->addColumn('expiry_date',    fn (InventoryBatch $b) => $b->expiry_date?->format('Y-m-d') ?? 'N/A')
            ->addColumn('days_remaining', function (InventoryBatch $b) {
                if (! $b->expiry_date) {
                    return null;
                }
                return (int) now()->startOfDay()->diffInDays($b->expiry_date->copy()->startOfDay(), false);
            })
            ->addColumn('status_badge', function (InventoryBatch $b) {
                if (! $b->expiry_date) {
                    return '<span style="background:#e2e8f0;color:#475569;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">N/A</span>';
                }
                $days = (int) now()->startOfDay()->diffInDays($b->expiry_date->copy()->startOfDay(), false);
                if ($days < 0) {
                    return '<span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">Expired</span>';
                }
                if ($days <= 30) {
                    return '<span style="background:#ffe4e6;color:#be123c;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">Critical</span>';
                }
                if ($days <= 90) {
                    return '<span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">Warning</span>';
                }
                return '<span style="background:#d1fae5;color:#065f46;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px">OK</span>';
            })
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function expiryExcel(Request $request)
    {
        $rows = $this->expiryQuery($request)->get()->map(function (InventoryBatch $b) {
            $days   = $b->expiry_date ? (int) now()->startOfDay()->diffInDays($b->expiry_date->copy()->startOfDay(), false) : null;
            $status = match(true) {
                $days === null => 'N/A',
                $days < 0      => 'Expired',
                $days <= 30    => 'Critical',
                $days <= 90    => 'Warning',
                default        => 'OK',
            };
            return [
                $b->batch_no ?? 'N/A',
                $b->sku?->sku_code ?? 'N/A',
                $b->sku?->product?->name ?? 'N/A',
                $b->sku?->variant_name ?: 'Single',
                $b->sku?->product?->category?->name ?? 'N/A',
                $b->warehouse?->name ?? 'N/A',
                $b->supplier?->name ?? 'N/A',
                (int) $b->available_quantity,
                $b->expiry_date?->format('Y-m-d') ?? 'N/A',
                $days ?? 'N/A',
                $status,
            ];
        });

        return $this->downloadCsv('expiry-report-' . now()->format('Y-m-d-His'), [
            'Batch No', 'SKU Code', 'Product', 'Variant', 'Category', 'Warehouse', 'Supplier', 'Available Qty', 'Expiry Date', 'Days Remaining', 'Status',
        ], $rows);
    }

    // ─── Sales Commission Report ──────────────────────────────────────────────

    public function commissionShow()
    {
        $salespersons = SalesStaffProfile::with(['user', 'commissionPlan'])
            ->where('is_salesperson', true)
            ->where('is_active', true)
            ->get();

        $plans = SalesCommissionPlan::where('is_active', true)->orderBy('name')->get();

        return view('report.commission-report', compact('salespersons', 'plans'));
    }

    public function commissionData(Request $request)
    {
        $year       = (int) ($request->year ?? now()->year);
        $month      = $request->filled('month') ? (int) $request->month : null;
        $userId     = $request->filled('user_id') ? (int) $request->user_id : null;
        $customRate = $request->filled('custom_rate') ? (float) $request->custom_rate : null;

        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        $rows = SalesOrder::query()
            ->select([
                'cashier_id',
                DB::raw('YEAR(order_date) as yr'),
                DB::raw('MONTH(order_date) as mo'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(COALESCE(sub_total, grand_total)) as gross_sales'),
                DB::raw('SUM(COALESCE(item_discount_total,0) + COALESCE(cart_discount_total,0)) as total_discount'),
            ])
            ->whereIn('status', ['delivered', 'completed'])
            ->whereNotNull('cashier_id')
            ->whereYear('order_date', $year)
            ->when($month,  fn ($q) => $q->whereMonth('order_date', $month))
            ->when($userId, fn ($q) => $q->where('cashier_id', $userId))
            ->groupBy('cashier_id', DB::raw('YEAR(order_date)'), DB::raw('MONTH(order_date)'))
            ->orderBy('cashier_id')
            ->orderBy(DB::raw('YEAR(order_date)'))
            ->orderBy(DB::raw('MONTH(order_date)'))
            ->get();

        $profiles = SalesStaffProfile::with(['user', 'commissionPlan'])
            ->whereIn('user_id', $rows->pluck('cashier_id')->unique()->filter())
            ->get()
            ->keyBy('user_id');

        $grouped = [];

        foreach ($rows as $row) {
            $profile  = $profiles->get($row->cashier_id);
            $plan     = $profile?->commissionPlan;
            $userName = $profile?->user?->name ?? ('User #' . $row->cashier_id);
            $empCode  = $profile?->employee_code ?? '—';
            $gross    = (float) $row->gross_sales;
            $net      = $gross - (float) $row->total_discount;

            [$rate, $commAmount, $planName] = $this->calcCommissionValues($plan, $gross, $net, $customRate);

            $key = $row->cashier_id;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'user_id'          => $row->cashier_id,
                    'name'             => $userName,
                    'emp_code'         => $empCode,
                    'plan_name'        => $planName,
                    'plan_type'        => $plan?->calculation_type ?? 'percentage',
                    'months'           => [],
                    'total_orders'     => 0,
                    'total_gross'      => 0.0,
                    'total_net'        => 0.0,
                    'total_commission' => 0.0,
                ];
            }

            $grouped[$key]['months'][] = [
                'month_num'   => (int) $row->mo,
                'month_name'  => $monthNames[$row->mo],
                'order_count' => (int) $row->order_count,
                'gross_sales' => round($gross, 2),
                'net_sales'   => round($net, 2),
                'rate'        => $rate,
                'commission'  => $commAmount,
            ];

            $grouped[$key]['total_orders']     += (int) $row->order_count;
            $grouped[$key]['total_gross']      += $gross;
            $grouped[$key]['total_net']        += $net;
            $grouped[$key]['total_commission'] += $commAmount;
        }

        $result = array_values($grouped);

        $summary = [
            'total_sales'      => round(collect($result)->sum('total_gross'), 2),
            'total_commission' => round(collect($result)->sum('total_commission'), 2),
            'person_count'     => count($result),
            'order_count'      => collect($result)->sum('total_orders'),
        ];

        return response()->json(['rows' => $result, 'summary' => $summary]);
    }

    public function commissionExcel(Request $request)
    {
        $year       = (int) ($request->year ?? now()->year);
        $month      = $request->filled('month') ? (int) $request->month : null;
        $userId     = $request->filled('user_id') ? (int) $request->user_id : null;
        $customRate = $request->filled('custom_rate') ? (float) $request->custom_rate : null;

        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        $rows = SalesOrder::query()
            ->select([
                'cashier_id',
                DB::raw('YEAR(order_date) as yr'),
                DB::raw('MONTH(order_date) as mo'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(COALESCE(sub_total, grand_total)) as gross_sales'),
                DB::raw('SUM(COALESCE(item_discount_total,0) + COALESCE(cart_discount_total,0)) as total_discount'),
            ])
            ->whereIn('status', ['delivered', 'completed'])
            ->whereNotNull('cashier_id')
            ->whereYear('order_date', $year)
            ->when($month,  fn ($q) => $q->whereMonth('order_date', $month))
            ->when($userId, fn ($q) => $q->where('cashier_id', $userId))
            ->groupBy('cashier_id', DB::raw('YEAR(order_date)'), DB::raw('MONTH(order_date)'))
            ->orderBy('cashier_id')
            ->orderBy(DB::raw('YEAR(order_date)'))
            ->orderBy(DB::raw('MONTH(order_date)'))
            ->get();

        $profiles = SalesStaffProfile::with(['user', 'commissionPlan'])
            ->whereIn('user_id', $rows->pluck('cashier_id')->unique()->filter())
            ->get()
            ->keyBy('user_id');

        $csvRows = $rows->map(function ($row) use ($profiles, $customRate, $monthNames) {
            $profile = $profiles->get($row->cashier_id);
            $plan    = $profile?->commissionPlan;
            $gross   = (float) $row->gross_sales;
            $net     = $gross - (float) $row->total_discount;

            [$rate, $commAmount, $planName] = $this->calcCommissionValues($plan, $gross, $net, $customRate);

            $rateLabel = $customRate !== null
                ? $customRate . '%'
                : ($plan ? ($plan->calculation_type === 'percentage' ? $rate . '%' : 'Fixed ' . $rate) : '—');

            return [
                $profile?->user?->name ?? ('User #' . $row->cashier_id),
                $profile?->employee_code ?? '—',
                $monthNames[$row->mo] . ' ' . $row->yr,
                $planName,
                $rateLabel,
                (int) $row->order_count,
                round($gross, 2),
                round($net, 2),
                round($commAmount, 2),
            ];
        });

        $filename = 'commission-report-' . $year
            . ($month ? '-' . str_pad($month, 2, '0', STR_PAD_LEFT) : '')
            . '-' . now()->format('His');

        return $this->downloadCsv($filename, [
            'Salesperson', 'Employee Code', 'Period', 'Commission Plan', 'Rate',
            'Orders', 'Gross Sales', 'Net Sales', 'Commission Amount',
        ], $csvRows);
    }

    private function calcCommissionValues(?SalesCommissionPlan $plan, float $gross, float $net, ?float $customRate): array
    {
        if ($customRate !== null) {
            return [$customRate, round($gross * $customRate / 100, 2), 'Custom (' . $customRate . '%)'];
        }

        if (! $plan) {
            return [0, 0, 'No Plan'];
        }

        $basis = $plan->base_amount_type === 'gross_sale' ? $gross : $net;

        if ($plan->min_target_amount && $basis < (float) $plan->min_target_amount) {
            return [(float) $plan->rate, 0, $plan->name . ' (below target)'];
        }

        $commission = $plan->calculation_type === 'percentage'
            ? $basis * (float) $plan->rate / 100
            : (float) $plan->rate;

        if ($plan->max_commission_amount) {
            $commission = min($commission, (float) $plan->max_commission_amount);
        }

        return [(float) $plan->rate, round($commission, 2), $plan->name];
    }

    // ─── Stockout Prediction Report ──────────────────────────────────────────────

    public function stockoutPredictionShow()
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();

        $categories = Category::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $stats      = $this->calcStockoutStats(30, null);

        return view('report.stockout-prediction-report', compact('branches', 'categories', 'warehouses', 'stats'));
    }

    public function stockoutPredictionList(Request $request)
    {
        $predictions = $this->buildStockoutPredictions($request);

        return response()->json(['data' => $predictions->values()]);
    }

    public function stockoutPredictionExcel(Request $request)
    {
        $predictions = $this->buildStockoutPredictions($request);

        $rows = $predictions->map(fn ($row) => [
            $row['sku_code'],
            $row['product_name'],
            $row['variant'],
            $row['category'],
            $row['brand'],
            $row['branch'],
            $row['warehouse'],
            $row['current_stock'],
            $row['reorder_level'],
            $row['total_sold'],
            $row['avg_daily_sales'],
            $row['days_remaining'] ?? 'N/A',
            $row['stockout_date']  ?? 'N/A',
            match ($row['risk_level']) {
                'critical' => 'Critical (stockout ≤7 days)',
                'high'     => 'High (≤14 days)',
                'medium'   => 'Medium (≤30 days)',
                'safe'     => 'Safe (>30 days)',
                default    => 'No Sales Data',
            },
        ]);

        return $this->downloadCsv('stockout-prediction-' . now()->format('Y-m-d-His'), [
            'SKU Code', 'Product', 'Variant', 'Category', 'Brand', 'Branch', 'Warehouse',
            'Current Stock', 'Reorder Level', 'Sold in Period', 'Avg Daily Sales (units)',
            'Days Remaining', 'Predicted Stockout Date', 'Risk Level',
        ], $rows);
    }

    private function buildStockoutPredictions(Request $request): \Illuminate\Support\Collection
    {
        $period   = max(1, (int) ($request->period ?? 30));
        $branchId = $this->resolveReportBranchId($request);
        $fromDate = now()->subDays($period)->startOfDay();

        $salesSub = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'so.id', '=', 'soi.sales_order_id')
            ->select('soi.sku_id', DB::raw('SUM(soi.quantity - COALESCE(soi.returned_quantity, 0)) as total_sold'))
            ->whereIn('so.status', ['delivered', 'completed'])
            ->where('so.order_date', '>=', $fromDate)
            ->when($branchId, fn ($q) => $q->where('so.branch_id', $branchId))
            ->groupBy('soi.sku_id');

        $rows = DB::table('stock_balances as sb')
            ->join('product_skus as ps', 'ps.id', '=', 'sb.sku_id')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('brands as br', 'br.id', '=', 'p.brand_id')
            ->leftJoin('branches as b', 'b.id', '=', 'sb.branch_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sb.warehouse_id')
            ->leftJoinSub($salesSub, 'sales', 'sales.sku_id', '=', 'sb.sku_id')
            ->select([
                'ps.sku_code',
                'p.name as product_name',
                DB::raw("IF(ps.variant_name IS NULL OR ps.variant_name = '', 'Single', ps.variant_name) as variant"),
                DB::raw("COALESCE(c.name, 'N/A') as category"),
                DB::raw("COALESCE(br.name, 'N/A') as brand"),
                DB::raw("COALESCE(b.name, 'N/A') as branch"),
                DB::raw("COALESCE(w.name, 'N/A') as warehouse"),
                DB::raw('sb.available_quantity as current_stock'),
                DB::raw('COALESCE(sb.reorder_level, 0) as reorder_level'),
                DB::raw('COALESCE(sales.total_sold, 0) as total_sold'),
            ])
            ->where('sb.available_quantity', '>', 0)
            ->when($branchId,                        fn ($q) => $q->where('sb.branch_id',    $branchId))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('sb.warehouse_id', $request->warehouse_id))
            ->when($request->filled('category_id'),  fn ($q) => $q->where('p.category_id',   $request->category_id))
            ->get();

        $predictions = $rows->map(function ($row) use ($period) {
            $totalSold     = (float) $row->total_sold;
            $avgDailySales = $totalSold / $period;
            $currentStock  = (int) $row->current_stock;

            if ($avgDailySales > 0) {
                $daysRemaining = (int) ceil($currentStock / $avgDailySales);
                $stockoutDate  = now()->addDays($daysRemaining)->format('Y-m-d');
            } else {
                $daysRemaining = null;
                $stockoutDate  = null;
            }

            $riskLevel = match (true) {
                $daysRemaining === null => 'no_data',
                $daysRemaining <= 7    => 'critical',
                $daysRemaining <= 14   => 'high',
                $daysRemaining <= 30   => 'medium',
                default                => 'safe',
            };

            return [
                'sku_code'        => $row->sku_code,
                'product_name'    => $row->product_name,
                'variant'         => $row->variant,
                'category'        => $row->category,
                'brand'           => $row->brand,
                'branch'          => $row->branch,
                'warehouse'       => $row->warehouse,
                'current_stock'   => $currentStock,
                'reorder_level'   => (int) $row->reorder_level,
                'total_sold'      => (int) $totalSold,
                'avg_daily_sales' => round($avgDailySales, 2),
                'days_remaining'  => $daysRemaining,
                'days_sort'       => $daysRemaining ?? 999999,
                'stockout_date'   => $stockoutDate ?? '',
                'risk_level'      => $riskLevel,
            ];
        });

        if ($request->filled('risk_level')) {
            $predictions = $predictions->where('risk_level', $request->risk_level);
        }

        return $predictions->sortBy('days_sort')->values();
    }

    private function calcStockoutStats(int $period, ?int $branchId): array
    {
        $fromDate = now()->subDays($period)->startOfDay();

        $salesSub = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'so.id', '=', 'soi.sales_order_id')
            ->select('soi.sku_id', DB::raw('SUM(soi.quantity - COALESCE(soi.returned_quantity, 0)) as total_sold'))
            ->whereIn('so.status', ['delivered', 'completed'])
            ->where('so.order_date', '>=', $fromDate)
            ->when($branchId, fn ($q) => $q->where('so.branch_id', $branchId))
            ->groupBy('soi.sku_id');

        $rows = DB::table('stock_balances as sb')
            ->leftJoinSub($salesSub, 'sales', 'sales.sku_id', '=', 'sb.sku_id')
            ->select([
                'sb.available_quantity',
                DB::raw('COALESCE(sales.total_sold, 0) as total_sold'),
            ])
            ->where('sb.available_quantity', '>', 0)
            ->when($branchId, fn ($q) => $q->where('sb.branch_id', $branchId))
            ->get();

        $stats = ['critical' => 0, 'high' => 0, 'medium' => 0, 'safe' => 0, 'no_data' => 0];

        foreach ($rows as $row) {
            $avgDaily = (float) $row->total_sold / $period;

            if ($avgDaily <= 0) { $stats['no_data']++; continue; }

            $days = (int) ceil($row->available_quantity / $avgDaily);

            if ($days <= 7)      $stats['critical']++;
            elseif ($days <= 14) $stats['high']++;
            elseif ($days <= 30) $stats['medium']++;
            else                 $stats['safe']++;
        }

        return $stats;
    }

    private function stockValueExpr(): \Illuminate\Contracts\Database\Query\Expression
    {
        // Weighted average purchase_price from available batches in the same warehouse,
        // falling back to product_skus.cost_price when no batch purchase_price is set.
        return DB::raw(
            'SUM(available_quantity * COALESCE(' .
            '(SELECT SUM(ib.available_quantity * ib.purchase_price) / NULLIF(SUM(ib.available_quantity), 0)' .
            ' FROM inventory_batches ib' .
            ' WHERE ib.sku_id = stock_balances.sku_id AND ib.warehouse_id = stock_balances.warehouse_id' .
            ' AND ib.available_quantity > 0 AND ib.purchase_price > 0),' .
            '(SELECT cost_price FROM product_skus WHERE id = stock_balances.sku_id),' .
            '0)) as total_value'
        );
    }

    private function expiryQuery(Request $request)
    {
        $today = now()->toDateString();

        $query = InventoryBatch::query()
            ->with(['sku.product.category', 'sku.product.brand', 'supplier', 'warehouse'])
            ->whereNotNull('expiry_date')
            ->where('available_quantity', '>', 0);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('sku.product', fn ($q) => $q->where('category_id', $request->category_id));
        }

        if ($request->filled('expiry_from')) {
            $query->whereDate('expiry_date', '>=', $request->expiry_from);
        }

        if ($request->filled('expiry_to')) {
            $query->whereDate('expiry_date', '<=', $request->expiry_to);
        }

        if ($request->filled('expiry_status')) {
            $status = $request->expiry_status;
            if ($status === 'expired') {
                $query->whereDate('expiry_date', '<', $today);
            } elseif ($status === 'critical') {
                $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString());
            } elseif ($status === 'warning') {
                $query->whereDate('expiry_date', '>', now()->addDays(30)->toDateString())->whereDate('expiry_date', '<=', now()->addDays(90)->toDateString());
            } elseif ($status === 'ok') {
                $query->whereDate('expiry_date', '>', now()->addDays(90)->toDateString());
            }
        }

        return $query->orderBy('expiry_date');
    }
}
