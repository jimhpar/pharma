@extends('layouts.main')
@php
    use App\Support\Currency;
    use App\Support\DateFormatter;

    $customer = $order->customer;
    $customerName = $customer?->name ?? $customer?->user?->name ?? 'Walk-in Customer';
    $customerEmail = $customer?->email ?? $customer?->user?->email ?? 'N/A';
    $paidTotal = (float) $order->payments->sum('amount');
    $dueTotal = max(0, (float) $order->grand_total - $paidTotal);
    $totalDiscount = (float) ($order->item_discount_total ?? 0) + (float) ($order->cart_discount_total ?? 0);
    $couponUsages = $order->coupons ?? collect();
    $couponDiscountTotal = (float) $couponUsages->sum(fn ($coupon) => (float) ($coupon->pivot?->discount_amount ?? 0));
    $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');
    $formatAmount = fn ($amount) => number_format((float) $amount, 2);
    $user = auth()->user();
    $canManageOrder = $user?->hasRoleSlug('super-admin') || $user?->hasPermissionSlug('orders.manage', $order->branch_id);
    $canManagePayments = $user?->hasRoleSlug('super-admin') || $user?->hasPermissionSlug('payments.manage', $order->branch_id);
@endphp
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Order Details</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Orders</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section id="basic-horizontal-layouts">
        <div class="row match-height">
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Order Details</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Customer Details</h5>
                                    <span>Name: {{ $customerName }}</span><br>
                                    <span>Email: {{ $customerEmail }}</span><br>
                                    <span>Phone: {{ $customer?->phone ?? 'N/A' }}</span>
                                </div>

                                <div class="col-md-6">
                                    <h5>Order Summary</h5>
                                    <span>Invoice No: {{ $order->invoice_no ?? $order->order_no }}</span><br>
                                    <span>Sale Type: {{ $order->sale_type }}</span><br>
                                    <span>Sales Channel: {{ $order->sales_channel_label }}</span><br>
                                    <span>Payment Method: {{ $order->payment_method }}</span><br>
                                    <span>Order Status: {{ $order->status_label }}</span><br>
                                    <span>Payment Status: {{ $order->payment_status_label }}</span><br>
                                    <span>Fulfillment: {{ $order->fulfillment_status_label }}</span><br>
                                    <span>Coupon: {{ $couponUsages->isNotEmpty() ? $couponUsages->pluck('code')->implode(', ') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Shipping & POS Info</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <span>Branch: {{ $order->branch?->name ?? 'N/A' }}</span><br>
                            <span>Warehouse: {{ $order->warehouse?->name ?? 'N/A' }}{{ $order->warehouse?->code ? ' (' . $order->warehouse->code . ')' : '' }}</span><br>
                            <span>Cashier: {{ $order->cashier?->name ?? 'N/A' }}</span><br>
                            <span>Shipment Zone: {{ $order->shipment_zone_name ?? 'N/A' }}</span><br>
                                    <span>Order Date: {{ DateFormatter::dateTime($order->order_date ?? $order->created_at, 'N/A') }}</span><br>
                            <span>Billing Address: {{ $customer?->billing_address ?? 'N/A' }}</span><br>
                            <span>Shipping Address: {{ $customer?->shipping_address ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Order Items</h4>
                        <div class="d-flex gap-2">
                            @if($canManageOrder)
                            <button
                                type="button"
                                class="btn btn-warning"
                                onclick="statusChange({{ $order->id }})"
                            >
                                Edit Status
                            </button>
                            @endif
                            <a
                                href="{{ route('order.invoice', $order->id) }}?print=1"
                                target="_blank"
                                class="btn btn-primary"
                            >
                                Print A4 Invoice
                            </a>
                            <a
                                href="{{ route('order.invoice.thermal', $order->id) }}?print=1"
                                target="_blank"
                                class="btn btn-secondary"
                            >
                                Print Thermal
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 48px;">SL</th>
                                            <th style="min-width: 280px;">Product Details</th>
                                            <th style="min-width: 300px;">Stock Source</th>
                                            <th>Qty</th>
                                            <th>Returned</th>
                                            <th>MRP</th>
                                            <th>Discount</th>
                                            <th>Cost</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->items as $item)
                                        @php
                                            $sku = $item->sku;
                                            $product = $sku?->product;
                                            $skuCode = $sku?->sku_code;
                                            $productCode = $sku?->product_code;
                                            $showProductCode = $productCode && $productCode !== $skuCode;
                                            $batch = $item->batch;
                                            $warehouse = $item->warehouse ?? $batch?->warehouse ?? $order->warehouse;
                                            $stockBalance = $batch?->stockBalances?->first(function ($balance) use ($item, $order, $warehouse) {
                                                return (int) $balance->sku_id === (int) $item->sku_id
                                                    && (int) $balance->branch_id === (int) $order->branch_id
                                                    && (int) $balance->warehouse_id === (int) ($item->warehouse_id ?? $warehouse?->id);
                                            });

                                            if (!$stockBalance) {
                                                $stockBalance = $sku?->stockBalances?->first(function ($balance) use ($item, $order, $batch, $warehouse) {
                                                    $sameBatch = $item->batch_id ? (int) $balance->batch_id === (int) $item->batch_id : true;

                                                    return (int) $balance->branch_id === (int) $order->branch_id
                                                        && (int) $balance->warehouse_id === (int) ($item->warehouse_id ?? $warehouse?->id)
                                                        && $sameBatch;
                                                });
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-bold-500">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $product?->name ?? 'N/A' }}</div>
                                                <div class="small text-muted mt-1">
                                                    Type: {{ $product?->type_label ?? 'N/A' }}
                                                    @if ($product?->brand?->name)
                                                        <span class="mx-1">|</span> Company: {{ $product->brand->name }}
                                                    @endif
                                                    @if ($product?->category?->name)
                                                        <span class="mx-1">|</span> Category: {{ $product->category->name }}
                                                    @endif
                                                </div>
                                                @if ($sku?->variant_name)
                                                    <div class="small mt-1">Variation: <span class="fw-semibold">{{ $sku->variant_name }}</span></div>
                                                @endif
                                                <div class="small text-muted mt-1">
                                                    SKU: {{ $skuCode ?? $productCode ?? 'N/A' }}
                                                    @if ($showProductCode)
                                                        <span class="mx-1">|</span> Product Code: {{ $productCode }}
                                                    @endif
                                                    @if ($sku?->barcode)
                                                        <span class="mx-1">|</span> Barcode: {{ $sku->barcode }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div><span class="fw-semibold">Branch:</span> {{ $stockBalance?->branch?->name ?? $order->branch?->name ?? 'N/A' }}</div>
                                                <div><span class="fw-semibold">Warehouse:</span> {{ $warehouse?->name ?? 'N/A' }}{{ $warehouse?->code ? ' (' . $warehouse->code . ')' : '' }}</div>
                                                <div><span class="fw-semibold">Batch:</span> {{ $batch?->batch_no ?? 'N/A' }}</div>
                                                <div class="small text-muted">
                                                    Supplier: {{ $batch?->supplier?->name ?? 'N/A' }}
                                                    @if ($batch?->expiry_date)
                                                        <span class="mx-1">|</span> Expiry: {{ DateFormatter::date($batch->expiry_date) }}
                                                    @endif
                                                </div>
                                                <div class="small mt-1">
                                                    <span class="badge bg-light-primary text-primary">Available: {{ $formatQty($stockBalance?->available_quantity ?? $batch?->available_quantity ?? 0) }}</span>
                                                    <span class="badge bg-light-secondary text-secondary">Stock Alert: {{ $formatQty($stockBalance?->reorder_level ?? 0) }}</span>
                                                </div>
                                            </td>
                                            <td class="text-bold-500">{{ $formatQty($item->quantity) }}</td>
                                            <td class="text-bold-500">{{ $formatQty($item->returned_quantity ?? 0) }}</td>
                                            <td class="text-bold-500">{{ $formatAmount($item->unit_price) }}</td>
                                            <td class="text-bold-500">{{ $formatAmount($item->discount_amount) }}</td>
                                            <td class="text-bold-500">{{ $formatAmount($item->cost_price ?? 0) }}</td>
                                            <td class="text-bold-500">{{ $formatAmount($item->line_total) }}</td>
                                        </tr>
                                        @endforeach
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end fw-bold">Subtotal</td>
                                            <td class="fw-bold">{{ Currency::format($order->sub_total ?? 0) }}</td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end fw-bold">Discount</td>
                                            <td class="fw-bold">{{ Currency::format($totalDiscount) }}</td>
                                        </tr>
                                        @if ($couponUsages->isNotEmpty())
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end fw-bold">
                                                Included Coupon Discount
                                                <span class="badge bg-light-success text-success ms-1">{{ $couponUsages->pluck('code')->implode(', ') }}</span>
                                            </td>
                                            <td class="text-success fw-bold">- {{ Currency::format($couponDiscountTotal) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end fw-bold">VAT</td>
                                            <td class="fw-bold">{{ Currency::format($order->tax_total ?? 0) }}</td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end fw-bold">Delivery Fee</td>
                                            <td class="fw-bold">{{ Currency::format($order->shipping_fee ?? 0) }}</td>
                                        </tr>
                                        @if (($order->point_discount_amount ?? 0) > 0)
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end fw-bold">Point Discount <span class="badge bg-warning text-dark ms-1">{{ $order->redeemed_points }} pts</span></td>
                                            <td class="text-success fw-bold">- {{ Currency::format($order->point_discount_amount) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="table-secondary">
                                            <td colspan="8" class="text-end fw-bold">Total</td>
                                            <td class="fw-bold">{{ Currency::format($order->grand_total) }}</td>
                                        </tr>
                                        @if (($order->earned_points ?? 0) > 0)
                                        <tr class="table-light">
                                            <td colspan="8" class="text-end text-success fw-bold">Points Earned</td>
                                            <td class="text-success fw-bold">+{{ $order->earned_points }} pts</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Payments</h4>
                        <h4 class="text-warning">Due: {{ Currency::format($dueTotal) }}</h4>
                        @if($canManagePayments)
                        @if (!in_array($order->status, ['draft', 'cancelled', 'returned'], true) && $order->payment_status !== 'paid')
                        <button class="btn btn-primary" onclick="addPayment({{ $order->id }})">Add Payment</button>
                        @endif
                        @endif
                    </div>

                    <div class="card-content">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-lg">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Method</th>
                                            <th>Purpose</th>
                                            <th>Note</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($order->payments as $payment)
                                        <tr>
                                            <td class="text-bold-500">{{ $payment->id }}</td>
                                            <td class="text-bold-500">{{ $payment->payment_method_label }}</td>
                                            <td class="text-bold-500">{{ ucwords(str_replace('_', ' ', $payment->payment_purpose)) }}</td>
                                            <td class="text-bold-500">{{ $payment->note ?: 'N/A' }}</td>
                                            <td class="text-bold-500">{{ Currency::format($payment->amount) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No payments recorded yet.</td>
                                        </tr>
                                        @endforelse
                                        <tr>
                                            <td colspan="4" class="text-right"><b>Total</b></td>
                                            <td>{{ Currency::format($paidTotal) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="paymentModal"></div>
            <div id="statusModal"></div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
function addPayment(data) {
    $.ajax({
        url: '{{ route('transaction.getPaymentModal') }}',
        method: 'post',
        data: {
            '_token': '{{ csrf_token() }}',
            'orderId': data,
        },
            success: function(data) {
                $('#paymentModal').html(data);
                $('#payment-Modal').modal('toggle');
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    toastr.error('You do not have permission to add payments.');
                    return;
                }

                if (xhr.status === 404) {
                    toastr.error('Order not found for the selected branch access.');
                    return;
                }

                toastr.error('Could not open the payment form.');
            }
        });
}

function statusChange(orderId) {
    $.ajax({
        url: '{{ route('orderStatus.getStatusModal') }}',
        method: 'post',
        data: {
            '_token': '{{ csrf_token() }}',
            'orderId': orderId,
        },
        success: function(data) {
            $('#statusModal').html(data);
            $('#status-Modal').modal('toggle');
        },
        error: function(xhr) {
            if (xhr.status === 403) {
                toastr.error('You do not have permission to edit order status.');
                return;
            }

            if (xhr.status === 404) {
                toastr.error('Order not found for the selected branch access.');
                return;
            }

            toastr.error('Could not open the order status form.');
        }
    });
}
</script>
@endsection
