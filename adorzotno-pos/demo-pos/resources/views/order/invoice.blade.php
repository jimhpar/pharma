<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->sale_type === 'Quotation' ? 'Quotation' : 'Receipt' }} #{{ $order->invoice_no ?? $order->order_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: 80mm 297mm;
            margin: 0;
        }

        :root {
            --ink: #111;
            --muted: #444;
            --line: #111;
            --paper: #fff;
        }

        body {
            width: 80mm;
            margin: 0 auto;
            background: #e5e7eb;
            color: var(--ink);
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 1.32;
        }

        .preview-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 14px 8px;
        }

        .action-bar {
            width: 80mm;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .action-bar button {
            border: 0;
            border-radius: 4px;
            padding: 7px 14px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-print { background: #111827; color: #fff; }
        .btn-close { background: #d1d5db; color: #111827; }

        .receipt-paper {
            width: 80mm;
            min-height: 120mm;
            background: var(--paper);
            padding: 4mm 3.5mm 7mm;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.22);
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .muted { color: var(--muted); }
        .tiny { font-size: 9px; }
        .small { font-size: 10px; }
        .title { font-size: 15px; font-weight: 800; line-height: 1.2; text-transform: uppercase; }
        .doc-title { font-size: 13px; font-weight: 800; letter-spacing: 0.6px; }

        .logo {
            display: block;
            max-width: 28mm;
            max-height: 12mm;
            object-fit: contain;
            margin: 0 auto 4px;
            filter: grayscale(1) contrast(1.25);
        }

        .shop-meta {
            max-width: 66mm;
            margin: 1px auto 0;
            color: var(--muted);
            font-size: 10px;
            word-break: break-word;
        }

        .rule {
            border: 0;
            border-top: 1px dashed var(--line);
            margin: 6px 0;
        }

        .rule-solid {
            border: 0;
            border-top: 1px solid var(--line);
            margin: 6px 0;
        }

        .kv-row {
            display: grid;
            grid-template-columns: 24mm 1fr;
            gap: 3mm;
            margin: 2px 0;
        }

        .kv-row .value {
            text-align: right;
            word-break: break-word;
        }

        .section-label {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 4px 0;
            font-weight: 800;
            text-transform: uppercase;
        }

        .section-label::before,
        .section-label::after {
            content: "";
            height: 1px;
            border-top: 1px dashed var(--line);
            flex: 1;
        }

        .receipt-head,
        .price-line,
        .total-row,
        .grand-row,
        .pay-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 4px;
            align-items: start;
        }

        .receipt-head {
            color: var(--muted);
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .receipt-item {
            padding: 4px 0;
            border-bottom: 1px dotted #555;
        }

        .item-name {
            font-weight: 800;
            word-break: break-word;
        }

        .item-meta {
            margin-top: 1px;
            color: var(--muted);
            font-size: 9px;
            word-break: break-word;
        }

        .price-line {
            margin-top: 3px;
        }

        .price-line .amount,
        .total-row .amount,
        .grand-row .amount,
        .pay-row .amount {
            text-align: right;
            white-space: nowrap;
        }

        .discount-line {
            font-weight: 800;
            color: var(--ink);
        }

        .total-row,
        .pay-row {
            margin: 2px 0;
        }

        .grand-row {
            margin: 5px 0;
            padding: 4px 0;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            font-weight: 900;
        }

        .footer-msg {
            margin-top: 7px;
            text-align: center;
            font-size: 10px;
            line-height: 1.45;
        }

        @media print {
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .preview-wrap {
                min-height: auto;
                display: block;
                padding: 0;
            }

            .action-bar { display: none; }

            .receipt-paper {
                width: 80mm;
                min-height: auto;
                box-shadow: none;
                padding: 3mm;
            }
        }
    </style>
</head>
<body>
@php
    use App\Support\DateFormatter;

    $isQuotation = $order->sale_type === 'Quotation';
    $docLabel = $isQuotation ? 'QUOTATION' : 'SALES RECEIPT';
    $customerName = $order->customer?->name ?? $order->customer?->user?->name ?? 'Walk-in Customer';
    $customerPhone = $order->customer?->phone ?? null;
    $cashierName = $order->cashier?->name ?? 'N/A';
    $branch = $order->branch;
    $orderDate = DateFormatter::dateTime($order->order_date ?? $order->created_at);
    $printedAt = DateFormatter::dateTime(now());
    $settings = \App\Models\Setting::query()->latest('id')->first();
    $logoPath = !empty($settings?->header_logo) ? url($settings->header_logo) : null;
    $companyName = $settings?->company_name ?: ($branch?->name ?? config('app.name', 'POS System'));
    $companyAddress = $settings?->office_address ?: ($branch?->address ?? null);
    $companyPhone = $settings?->phone ?: ($branch?->phone ?? null);
    $companyEmail = $settings?->email ?: ($branch?->email ?? null);
    $money = fn ($amount) => number_format((float) $amount, 2);
    $moneyWithCurrency = fn ($amount) => 'BDT ' . number_format((float) $amount, 2);
    $totalDiscount = (float) ($order->item_discount_total ?? 0) + (float) ($order->cart_discount_total ?? 0);
    $receiptGrandTotal = max(0, round((float) ($order->sub_total ?? 0) - $totalDiscount + (float) ($order->tax_total ?? 0) + (float) ($order->shipping_fee ?? 0), 2));
    $paidAmount = (float) $order->payments->sum('amount');
    $changeAmount = max(0, $paidAmount - $receiptGrandTotal);
    $dueAmount = max(0, $receiptGrandTotal - $paidAmount);
@endphp

@unless (request()->boolean('embed'))
<div class="preview-wrap">
    <div class="action-bar">
        <button class="btn-close" onclick="window.close()">Close</button>
        <button class="btn-print" onclick="printThermalReceiptManual()">Print</button>
    </div>
    <div class="receipt-paper">
@endunless

        <div class="center">
            @if ($logoPath)
                <img class="logo" src="{{ $logoPath }}" alt="{{ $companyName }}">
            @endif
            <div class="title">{{ $companyName }}</div>
            @if ($branch?->name)
                <div class="shop-meta">{{ $branch->name }}</div>
            @endif
            @if ($companyAddress)
                <div class="shop-meta">{{ $companyAddress }}</div>
            @endif
            @if ($companyPhone)
                <div class="shop-meta">Phone: {{ $companyPhone }}</div>
            @endif
            @if ($companyEmail)
                <div class="shop-meta">{{ $companyEmail }}</div>
            @endif
        </div>

        <hr class="rule-solid">
        <div class="center doc-title">{{ $docLabel }}</div>
        <hr class="rule">

        <div class="kv-row">
            <span>Invoice</span>
            <span class="value bold">{{ $order->invoice_no ?? $order->order_no }}</span>
        </div>
        <div class="kv-row">
            <span>Date</span>
            <span class="value">{{ $orderDate }}</span>
        </div>
        <div class="kv-row">
            <span>Customer</span>
            <span class="value">{{ $customerName }}</span>
        </div>
        @if ($customerPhone)
            <div class="kv-row">
                <span>Mobile</span>
                <span class="value">{{ $customerPhone }}</span>
            </div>
        @endif
        <div class="kv-row">
            <span>Cashier</span>
            <span class="value">{{ $cashierName }}</span>
        </div>
        <div class="kv-row">
            <span>Payment</span>
            <span class="value">{{ $order->payment_method }}</span>
        </div>

        <div class="section-label">Items</div>
        <div class="receipt-head">
            <span>Item / Qty x Rate</span>
            <span>Total</span>
        </div>

        @foreach ($order->items as $index => $item)
            @php
                $itemName = $item->sku?->product?->name ?? 'N/A';
                $variant = $item->sku?->variant_name ?: '';
                $productCode = $item->sku?->product_code ?? $item->sku?->sku_code ?? '';
                $brandName = $item->sku?->product?->brand?->name ?? '';
                $qty = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');
                $discountAmount = (float) ($item->discount_amount ?? 0);
                $grossAmount = (float) $item->unit_price * (float) $item->quantity;
            @endphp
            <div class="receipt-item">
                <div class="item-name">{{ $index + 1 }}. {{ $itemName }}</div>
                @if ($brandName || $variant || $productCode)
                    <div class="item-meta">
                        @if ($productCode)Code: {{ $productCode }}@endif
                        @if ($brandName){{ $productCode ? ' | ' : '' }}Brand: {{ $brandName }}@endif
                        @if ($variant){{ ($productCode || $brandName) ? ' | ' : '' }}{{ $variant }}@endif
                    </div>
                @endif
                <div class="price-line">
                    <span>{{ $qty }} x {{ $money($item->unit_price) }}</span>
                    <span class="amount">{{ $money($discountAmount > 0 ? $grossAmount : $item->line_total) }}</span>
                </div>
                @if ($discountAmount > 0)
                    <div class="price-line discount-line">
                        <span>Discount on this item</span>
                        <span class="amount">-{{ $money($discountAmount) }}</span>
                    </div>
                    <div class="price-line bold">
                        <span>Net total</span>
                        <span class="amount">{{ $money($item->line_total) }}</span>
                    </div>
                @endif
            </div>
        @endforeach

        <hr class="rule-solid">

        <div class="total-row">
            <span>Subtotal</span>
            <span class="amount">{{ $moneyWithCurrency($order->sub_total ?? 0) }}</span>
        </div>
        <div class="total-row">
            <span>Discount</span>
            <span class="amount">- {{ $moneyWithCurrency($totalDiscount) }}</span>
        </div>
        <div class="total-row">
            <span>VAT</span>
            <span class="amount">+ {{ $moneyWithCurrency($order->tax_total ?? 0) }}</span>
        </div>
        @if (($order->shipping_fee ?? 0) > 0)
            <div class="total-row">
                <span>Delivery</span>
                <span class="amount">+ {{ $moneyWithCurrency($order->shipping_fee) }}</span>
            </div>
        @endif

        <div class="grand-row">
            <span>GRAND TOTAL</span>
            <span class="amount">{{ $moneyWithCurrency($receiptGrandTotal) }}</span>
        </div>

        <div class="pay-row">
            <span>Paid</span>
            <span class="amount">{{ $moneyWithCurrency($paidAmount) }}</span>
        </div>
        @if ($changeAmount > 0)
            <div class="pay-row">
                <span>Change</span>
                <span class="amount">{{ $moneyWithCurrency($changeAmount) }}</span>
            </div>
        @endif
        @if ($dueAmount > 0)
            <div class="pay-row bold">
                <span>DUE</span>
                <span class="amount">{{ $moneyWithCurrency($dueAmount) }}</span>
            </div>
        @endif

        <hr class="rule">

        <div class="center small">Thank you for shopping with us</div>
        <div class="center tiny muted">{{ $order->invoice_no ?? $order->order_no }}</div>
        <div class="center tiny muted" style="margin-top:4px;">Printed: {{ $printedAt }}</div>

@unless (request()->boolean('embed'))
    </div>
</div>
@endunless

<script>
function printThermalReceiptManual() {
    const button = document.querySelector('.btn-print');
    if (button) {
        button.disabled = true;
        button.textContent = 'Select Printer...';
    }

    window.focus();
    window.print();

    setTimeout(function () {
        if (button) {
            button.disabled = false;
            button.textContent = 'Print';
        }
    }, 800);
}

@if (request()->boolean('print'))
window.addEventListener('load', function () {
    setTimeout(printThermalReceiptManual, 400);
});
@endif
</script>
</body>
</html>
