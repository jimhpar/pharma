<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function show(Request $request)
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();
        $salesChannels = SalesOrder::channelOptions();

        $monthOrders = SalesOrder::query()
            ->tap(fn ($query) => $this->scopeReportBranch($query, $request))
            ->whereIn('status', SalesOrder::REPORTABLE_SALES_STATUSES)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->get();

        $thisMonth = [
            'count'    => $monthOrders->count(),
            'revenue'  => $monthOrders->sum('grand_total'),
            'delivery' => $monthOrders->sum('shipping_fee'),
            'discount' => $monthOrders->sum(fn ($o) => (float) ($o->item_discount_total ?? 0) + (float) ($o->cart_discount_total ?? 0)),
        ];

        return view('report.sales-report', compact('branches', 'salesChannels', 'thisMonth'));
    }

    public function list(Request $request)
    {
        $orders = $this->salesReportQuery($request);

        $orderData         = $orders->get();
        $orderTotalSum     = $orderData->sum('grand_total');
        $deliveryChargeSum = $orderData->sum('shipping_fee');
        $orderDiscountSum  = $orderData->sum(fn ($o) => (float) ($o->item_discount_total ?? 0) + (float) ($o->cart_discount_total ?? 0));
        $orderCount        = $orderData->count();

        return DataTables()->of($orders)
            ->addColumn('order_date', fn (SalesOrder $order) =>
                DateFormatter::humanDateTime($order->created_at)
            )
            ->addColumn('customer', fn (SalesOrder $order) =>
                e($order->customer?->name
                    ?? $order->customer?->user?->name
                    ?? 'Walk-in Customer')
            )
            ->addColumn('branch_name', fn (SalesOrder $order) => e($order->branch?->name ?? 'N/A'))
            ->addColumn('sales_channel_badge', fn (SalesOrder $order) =>
                $this->channelBadge(SalesOrder::canonicalChannel($order->sales_channel))
            )
            ->addColumn('delivery_fee', fn (SalesOrder $order) =>
                Currency::format($order->shipping_fee)
            )
            ->addColumn('order_discount', fn (SalesOrder $order) =>
                Currency::format((float) ($order->item_discount_total ?? 0) + (float) ($order->cart_discount_total ?? 0))
            )
            ->addColumn('order_last_status', fn (SalesOrder $order) =>
                $this->orderStatusBadge($order->status)
            )
            ->addColumn('payment_last_status', fn (SalesOrder $order) =>
                $this->paymentStatusBadge($order->payment_status)
            )
            ->editColumn('grand_total', fn (SalesOrder $order) =>
                Currency::format($order->grand_total)
            )
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['customer', 'sales_channel_badge', 'order_last_status', 'payment_last_status'])
            ->with([
                'orderTotalSum'    => $orderTotalSum,
                'deliveryChargeSum'=> $deliveryChargeSum,
                'orderDiscountSum' => $orderDiscountSum,
                'orderCount'       => $orderCount,
            ])
            ->make(true);
    }

    public function excel(Request $request)
    {
        $rows = $this->salesReportQuery($request)->get()->map(fn (SalesOrder $order) => [
            $order->id,
            DateFormatter::dateTime($order->created_at),
            $order->branch?->name ?? 'N/A',
            $order->sales_channel_label,
            $order->customer?->name ?? $order->customer?->user?->name ?? 'Walk-in Customer',
            (float) $order->shipping_fee,
            (float) ($order->item_discount_total ?? 0) + (float) ($order->cart_discount_total ?? 0),
            (float) $order->grand_total,
            $order->status_label,
            $order->payment_status_label,
        ]);

        return $this->downloadCsv('sales-report-' . now()->format('Y-m-d-His'), [
            'Order ID', 'Date', 'Branch', 'Channel', 'Customer',
            'Delivery Charge', 'Order Discount', 'Order Total',
            'Order Status', 'Payment Status',
        ], $rows);
    }

    private function salesReportQuery(Request $request)
    {
        return SalesOrder::query()
            ->with(['branch', 'customer.user'])
            ->tap(fn ($query) => $this->scopeReportBranch($query, $request))
            ->whereIn('status', SalesOrder::REPORTABLE_SALES_STATUSES)
            ->when($request->filled('startDate'), fn ($q) => $q->whereDate('order_date', '>=', $request->startDate))
            ->when($request->filled('endDate'), fn ($q) => $q->whereDate('order_date', '<=', $request->endDate))
            ->when($request->filled('orderStatus'), fn ($q) => $q->where('status', $request->orderStatus))
            ->when($request->filled('paymentStatus'), fn ($q) => $q->where('payment_status', $request->paymentStatus))
            ->when($request->filled('salesChannel'), fn ($q) => $q->whereIn('sales_channel', SalesOrder::channelFilterValues($request->salesChannel)))
            ->latest();
    }

    private function orderStatusBadge(string $status): string
    {
        $map = [
            'draft'      => ['bg:#e2e8f0', 'color:#475569', 'Draft'],
            'pending'    => ['bg:#fef3c7', 'color:#92400e', 'Pending'],
            'confirmed'  => ['bg:#dbeafe', 'color:#1e40af', 'Confirmed'],
            'processing' => ['bg:#e0f2fe', 'color:#0369a1', 'Processing'],
            'packed'     => ['bg:#ede9fe', 'color:#5b21b6', 'Packed'],
            'shipped'    => ['bg:#e0e7ff', 'color:#3730a3', 'Shipped'],
            'delivered'  => ['bg:#d1fae5', 'color:#065f46', 'Delivered'],
            'completed'  => ['bg:#bbf7d0', 'color:#14532d', 'Completed'],
            'returned'   => ['bg:#ffedd5', 'color:#9a3412', 'Returned'],
            'cancelled'  => ['bg:#fee2e2', 'color:#991b1b', 'Cancelled'],
        ];

        [$bg, $color, $label] = $map[$status] ?? ['bg:#f1f5f9', 'color:#64748b', ucfirst($status)];

        return '<span style="'.$bg.';'.$color.';font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px;white-space:nowrap">'.$label.'</span>';
    }

    private function paymentStatusBadge(string $status): string
    {
        $map = [
            'unpaid'   => ['bg:#fee2e2', 'color:#991b1b', 'Unpaid'],
            'partial'  => ['bg:#fef3c7', 'color:#92400e', 'Partial'],
            'paid'     => ['bg:#d1fae5', 'color:#065f46', 'Paid'],
            'refunded' => ['bg:#dbeafe', 'color:#1e40af', 'Refunded'],
        ];

        [$bg, $color, $label] = $map[$status] ?? ['bg:#f1f5f9', 'color:#64748b', ucfirst($status)];

        return '<span style="'.$bg.';'.$color.';font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:6px;white-space:nowrap">'.$label.'</span>';
    }

    private function channelBadge(?string $channel): string
    {
        $map = [
            'pos'    => ['bg:#ede9fe', 'color:#5b21b6', '<i class="bi bi-display me-1"></i>POS'],
            'online' => ['bg:#fff7ed', 'color:#9a3412', '<i class="bi bi-globe me-1"></i>Online'],
        ];

        [$bg, $color, $label] = $map[$channel] ?? ['bg:#f1f5f9', 'color:#64748b', ucfirst($channel ?? 'N/A')];

        return '<span style="'.$bg.';'.$color.';font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:6px;white-space:nowrap">'.$label.'</span>';
    }
}
