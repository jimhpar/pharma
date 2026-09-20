<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->sale_type === 'Quotation' ? 'Quotation' : 'Invoice' }} #{{ $order->invoice_no ?? $order->order_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef1f5;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
        }
        .preview-wrap {
            min-height: 100vh;
            padding: 24px;
        }
        .action-bar {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 18px;
        }
        .action-bar button {
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print { background: #0d6efd; color: #fff; }
        .btn-close { background: #d1d5db; color: #111827; }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 18mm 16mm;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand h1 {
            margin: 0 0 8px;
            font-size: 28px;
            letter-spacing: 0.02em;
        }
        .brand-logo {
            display: block;
            max-width: 170px;
            max-height: 82px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .muted {
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
        }
        .doc-meta {
            text-align: right;
        }
        .doc-meta h2 {
            margin: 0 0 8px;
            font-size: 24px;
        }
        .doc-meta p {
            margin: 4px 0;
            font-size: 14px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }
        .info-card {
            border: 1px solid #dbe1ea;
            border-radius: 10px;
            padding: 14px 16px;
        }
        .info-card h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }
        .info-card p {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #dbe1ea;
            padding: 7px 8px;
            font-size: 13px;
            vertical-align: top;
        }
        .items-table th {
            background: #fff;
            color: #000;
            font-weight: 700;
            text-align: center;
        }
        .items-table .sl-col { width: 78px; text-align: center; }
        .items-table .product-col { width: 36%; }
        .items-table .qty-col { width: 22%; }
        .product-detail {
            color: #6b7280;
            font-size: 12px;
            margin-top: 3px;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            width: 340px;
            margin-left: auto;
            margin-top: 18px;
        }
        .summary td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .summary .grand td,
        .summary .due td {
            font-weight: 700;
            font-size: 16px;
        }
        .footer {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px dashed #9ca3af;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
        @page {
            size: A4;
            margin: 12mm;
        }
        @media print {
            body { background: #fff; }
            .preview-wrap { padding: 0; min-height: auto; }
            .action-bar { display: none; }
            .sheet {
                width: auto;
                min-height: auto;
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
@php
    use App\Support\Currency;
    use App\Support\DateFormatter;

    $isQuotation = $order->sale_type === 'Quotation';
    $documentTitle = $isQuotation ? 'Quotation' : 'Invoice';
    $branch = $order->branch;
    $customer = $order->customer;
    $customerName = $customer?->name ?? $customer?->user?->name ?? 'Walk-in Customer';
    $customerEmail = $customer?->email ?? $customer?->user?->email ?? 'N/A';
    $paidTotal = (float) $order->payments->sum('amount');
    $dueTotal = max(0, (float) $order->grand_total - $paidTotal);
    $settings = \App\Models\Setting::query()->latest('id')->first();
    $logoPath = !empty($settings?->header_logo)
        ? url($settings->header_logo)
        : url('public/admin/dist/assets/compiled/png/AdorzotnoLogo.png');
    $companyName = $settings?->company_name ?: ($branch?->name ?? config('app.name', 'POS System'));
    $companyAddress = $settings?->office_address ?: ($branch?->address ?? 'Address not available');
    $companyPhone = $settings?->phone ?: ($branch?->phone ?? 'N/A');
    $companyEmail = $settings?->email ?: ($branch?->email ?? 'N/A');
    $money = fn ($amount) => number_format((float) $amount, 2);
    $moneyWithCurrency = fn ($amount) => number_format((float) $amount, 2) . ' BDT';
    $totalDiscount = (float) ($order->item_discount_total ?? 0) + (float) ($order->cart_discount_total ?? 0);
@endphp

<div class="preview-wrap">
    <div class="action-bar">
        <button class="btn-close" onclick="window.close()">Close</button>
        <button class="btn-print" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        <div class="header">
            <div class="brand">
                @if ($logoPath)
                    <img class="brand-logo" src="{{ $logoPath }}" alt="{{ $companyName }}">
                @endif
                <h1>{{ $companyName }}</h1>
                <div class="muted">
                    @if ($branch?->name)
                        <div>{{ $branch->name }}</div>
                    @endif
                    <div>{{ $companyAddress }}</div>
                    <div>{{ $companyPhone }}</div>
                    <div>{{ $companyEmail }}</div>
                </div>
            </div>
            <div class="doc-meta">
                <h2>{{ strtoupper($documentTitle) }}</h2>
                <p><strong>Invoice #:</strong> {{ $order->invoice_no ?? $order->order_no }}</p>
                <p><strong>Date:</strong> {{ DateFormatter::dateTime($order->order_date ?? $order->created_at) }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>Customer Info</h3>
                <p><strong>Name:</strong> {{ $customerName }}</p>
                <p><strong>Email:</strong> {{ $customerEmail }}</p>
                <p><strong>Phone:</strong> {{ $customer?->phone ?? 'N/A' }}</p>
                <p><strong>Billing Address:</strong> {{ $customer?->billing_address ?? 'N/A' }}</p>
                <p><strong>Shipping Address:</strong> {{ $customer?->shipping_address ?? 'N/A' }}</p>
            </div>
            <div class="info-card">
                <h3>Order Info</h3>
                <p><strong>Sale Type:</strong> {{ $order->sale_type }}</p>
                <p><strong>Sales Channel:</strong> {{ $order->sales_channel_label }}</p>
                <p><strong>Payment Method:</strong> {{ $order->payment_method }}</p>
                <p><strong>Payment Status:</strong> {{ $order->payment_status_label }}</p>
                <p><strong>Fulfillment:</strong> {{ $order->fulfillment_status_label }}</p>
                <p><strong>Cashier:</strong> {{ $order->cashier?->name ?? 'N/A' }}</p>
                <p><strong>Warehouse:</strong> {{ $order->warehouse?->name ?? 'N/A' }}</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="sl-col">SL No.</th>
                        <th class="product-col">Products</th>
                        <th class="qty-col">Quantity</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $index => $item)
                        @php
                            $quantity = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');
                            $variant = $item->sku?->variant_name ?: '';
                            $productCode = $item->sku?->product_code ?? $item->sku?->sku_code ?? '';
                            $brandName = $item->sku?->product?->brand?->name ?? '';
                        @endphp
                        <tr>
                            <td class="sl-col">{{ $index + 1 }}</td>
                            <td>
                                {{ $item->sku?->product?->name ?? 'N/A' }}
                                @if ($brandName || $variant || $productCode)
                                    <div class="product-detail">
                                        @if($brandName) Company: {{ $brandName }}<br>@endif
                                        @if($variant) Variation: {{ $variant }}<br>@endif
                                        @if($productCode) Product Code: {{ $productCode }} @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $quantity }}@if($variant) x {{ $variant }}@endif</td>
                            <td class="text-right">{{ $money($item->unit_price) }}</td>
                            <td class="text-right">{{ $money($item->discount_amount) }}</td>
                            <td class="text-right">{{ $money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-responsive">
            <table class="summary">
                <tbody>
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right">{{ $moneyWithCurrency($order->sub_total ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Discount</td>
                        <td class="text-right">- {{ $moneyWithCurrency($totalDiscount) }}</td>
                    </tr>
                    <tr>
                        <td>VAT</td>
                        <td class="text-right">+ {{ $moneyWithCurrency($order->tax_total ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Shipping</td>
                        <td class="text-right">+ {{ $moneyWithCurrency($order->shipping_fee ?? 0) }}</td>
                    </tr>
                    @if (($order->point_discount_amount ?? 0) > 0)
                    <tr>
                        <td>Point Discount <small style="color:#6b7280">({{ $order->redeemed_points }} pts redeemed)</small></td>
                        <td class="text-right" style="color:#15803d">- {{ $moneyWithCurrency($order->point_discount_amount) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>Grand Total</td>
                    <td class="text-right">{{ $moneyWithCurrency($order->grand_total ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Paid</td>
                    <td class="text-right">{{ $moneyWithCurrency($paidTotal) }}</td>
                </tr>
                <tr class="due">
                    <td>Due</td>
                    <td class="text-right">{{ $moneyWithCurrency($dueTotal) }}</td>
                </tr>
            </tbody>
        </table>
        </div>

        {{-- Loyalty info footer --}}
        @if ($customer)
        <div style="border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;margin-top:14px;font-size:12px;background:#fafafa;">
            <strong>Customer:</strong> {{ $customerName }}
            @if ($customer->is_member)
                &nbsp;<span style="background:#16a34a;color:#fff;border-radius:999px;padding:1px 8px;font-size:10px;">★ Member</span>
            @endif
            &nbsp;&nbsp;
            @if (($order->earned_points ?? 0) > 0)
                <span style="color:#15803d"><strong>+{{ $order->earned_points }}</strong> pts earned this order.</span>
                &nbsp;&nbsp;
            @endif
            @if (($order->redeemed_points ?? 0) > 0)
                <span style="color:#b45309"><strong>{{ $order->redeemed_points }}</strong> pts redeemed ({{ $money($order->point_discount_amount) }} discount).</span>
                &nbsp;&nbsp;
            @endif
            <span style="color:#374151">Current balance: <strong>{{ number_format($customer->loyalty_points) }}</strong> pts</span>
        </div>
        @endif

        <div class="footer">
            Thank you for your business.
        </div>
    </div>
</div>

@if (request()->boolean('print'))
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif
</body>
</html>
