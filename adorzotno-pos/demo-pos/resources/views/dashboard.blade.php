@extends('layouts.main')

@section('main.content')
@php
$formatMoney = fn ($value) => \App\Support\Currency::format($value);
$scopeLabel = $isConsolidated ? 'Head Office Consolidated View' : ($selectedBranch?->name ?? 'Current Branch');
$collectionRate = max(0, min(100, (float) $dashboard['collection_rate']));
$todayVsGross = $dashboard['gross_sales'] > 0 ? max(0, min(100, ($dashboard['today_sales'] / $dashboard['gross_sales']) * 100)) : 0;
$stockAlertRate = $dashboard['active_products'] > 0 ? max(0, min(100, (($dashboard['low_stock_count'] + $dashboard['out_of_stock_count']) / $dashboard['active_products']) * 100)) : 0;
$customerGrowthRate = $dashboard['total_customers'] > 0 ? max(0, min(100, ($dashboard['new_customers_this_month'] / $dashboard['total_customers']) * 100)) : 0;
@endphp

<div class="dashboard-shell">
    <section class="dashboard-hero">
        <div class="row g-4 align-items-stretch">
            <div class="col-12 col-xl-7">
                <span class="dashboard-eyebrow mb-2">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    Live Business Overview
                </span>
                <p>{{ $scopeLabel }} with {{ $periodLabel }} sales health, stock movement, and customer activity in one screen.</p>

                <div class="row g-3 dashboard-hero-metrics mb-3">
                    <div class="col-12 col-md-4">
                        <div class="hero-metric hero-metric-sales h-100">
                            <span>Gross Sales</span>
                            <strong>{{ $formatMoney($dashboard['gross_sales']) }}</strong>
                            <small>{{ number_format($dashboard['total_orders']) }} finalized orders</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="hero-metric hero-metric-collection h-100">
                            <span>Collections</span>
                            <strong>{{ $formatMoney($dashboard['collected_amount']) }}</strong>
                            <small>{{ number_format($collectionRate, 1) }}% recovery rate</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="hero-metric hero-metric-today h-100">
                            <span>Today</span>
                            <strong>{{ $formatMoney($dashboard['today_sales']) }}</strong>
                            <small>{{ number_format($dashboard['today_orders']) }} orders today</small>
                        </div>
                    </div>
                </div>

                <div class="row g-3 dashboard-hero-insights">
                    <div class="col-6 col-lg-3">
                        <div class="hero-insight hero-insight-average h-100">
                            <span>Avg Order</span>
                            <strong>{{ $formatMoney($dashboard['average_order_value']) }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="hero-insight hero-insight-orders h-100">
                            <span>Total Orders</span>
                            <strong>{{ number_format($dashboard['total_orders']) }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="hero-insight hero-insight-stock h-100">
                            <span>Stock In / Out</span>
                            <strong>{{ number_format($dashboard['stock_in_this_month']) }} / {{ number_format($dashboard['stock_out_this_month']) }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="hero-insight hero-insight-customer h-100">
                            <span>New Customers</span>
                            <strong>{{ number_format($dashboard['new_customers_this_month']) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="dashboard-hero-panel h-100">
                    <div class="dashboard-filter-heading">
                        <h5>Dashboard Scope</h5>
                        <p>{{ $periodLabel }}</p>
                    </div>

                    <div class="scope-select-card dashboard-filter-card">
                        <form method="GET" action="{{ route('dashboard') }}" class="dashboard-filter-form">
                            <div class="row g-2 align-items-end">
                                @if($allowedBranches->isNotEmpty() && $hasCrossBranchAccess)
                                <div class="col-12">
                                    <label class="form-label" for="dashboard-branch-scope">Dashboard Scope</label>
                                    <select id="dashboard-branch-scope" name="branch_id" class="form-select">
                                        <option value="">All Branches</option>
                                        @foreach($availableBranches as $branch)
                                        <option value="{{ $branch->id }}" {{ (int) ($selectedBranch?->id ?? 0) === (int) $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-6 col-md-5">
                                    <label class="form-label" for="dashboard-start-date">From</label>
                                    <input id="dashboard-start-date" type="date" name="start_date" class="form-control" value="{{ $filterStartDate }}">
                                </div>
                                <div class="col-6 col-md-5">
                                    <label class="form-label" for="dashboard-end-date">To</label>
                                    <input id="dashboard-end-date" type="date" name="end_date" class="form-control" value="{{ $filterEndDate }}">
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="submit" class="btn btn-primary w-100" aria-label="Apply dashboard filter">
                                        <i class="bi bi-funnel"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-3 spotlight-list">
                        <div class="col-12">
                            <div class="spotlight-item">
                                <div>
                                    <strong>Outstanding Due</strong>
                                    <span>{{ $formatMoney($dashboard['due_amount']) }}</span>
                                </div>
                                <small>{{ $formatMoney($dashboard['today_collections']) }} collected today</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="spotlight-item">
                                <div>
                                    <strong>Low / Out of Stock</strong>
                                    <span>{{ number_format($dashboard['low_stock_count']) }} / {{ number_format($dashboard['out_of_stock_count']) }}</span>
                                </div>
                                <small>{{ number_format($dashboard['units_on_hand']) }} total units on hand</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-4">
            <div class="col-12 col-md-6 col-xl-3">
                <article class="dashboard-card metric-card h-100">
                    <div class="metric-card-header">
                        <div class="metric-card-label">
                            <span>Collections</span>
                            <h3>{{ $formatMoney($dashboard['collected_amount']) }}</h3>
                        </div>
                        <div class="metric-card-icon" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                    <small>{{ number_format($collectionRate, 1) }}% of gross sales collected</small>
                    <div class="metric-progress"><span style="width: {{ $collectionRate }}%; background: linear-gradient(90deg, #0f766e, #2dd4bf);"></span></div>
                </article>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <article class="dashboard-card metric-card h-100">
                    <div class="metric-card-header">
                        <div class="metric-card-label">
                            <span>Today's Sales</span>
                            <h3>{{ $formatMoney($dashboard['today_sales']) }}</h3>
                        </div>
                        <div class="metric-card-icon" style="background: linear-gradient(135deg, #2563eb, #60a5fa);">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    <small>{{ number_format($dashboard['today_orders']) }} orders, {{ number_format($todayVsGross, 1) }}% of total sales</small>
                    <div class="metric-progress"><span style="width: {{ $todayVsGross }}%; background: linear-gradient(90deg, #2563eb, #60a5fa);"></span></div>
                </article>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <article class="dashboard-card metric-card h-100">
                    <div class="metric-card-header">
                        <div class="metric-card-label">
                            <span>Stock Value</span>
                            <h3>{{ $formatMoney($dashboard['stock_value']) }}</h3>
                        </div>
                        <div class="metric-card-icon" style="background: linear-gradient(135deg, #7c3aed, #a78bfa);">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                    <small>{{ number_format($dashboard['active_products']) }} products across {{ number_format($dashboard['total_skus']) }} SKUs</small>
                    <div class="metric-progress"><span style="width: {{ max(12, 100 - $stockAlertRate) }}%; background: linear-gradient(90deg, #7c3aed, #a78bfa);"></span></div>
                </article>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <article class="dashboard-card metric-card h-100">
                    <div class="metric-card-header">
                        <div class="metric-card-label">
                            <span>New Customers</span>
                            <h3>{{ number_format($dashboard['new_customers_this_month']) }}</h3>
                        </div>
                        <div class="metric-card-icon" style="background: linear-gradient(135deg, #ea580c, #fb923c);">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <small>{{ number_format($dashboard['total_customers']) }} total customers in database</small>
                    <div class="metric-progress"><span style="width: {{ $customerGrowthRate }}%; background: linear-gradient(90deg, #ea580c, #fb923c);"></span></div>
                </article>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h5 class="dashboard-card-title">Operational Summary</h5>
                            <p>Core business metrics for order flow, stock health, and customer acquisition.</p>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        <div class="row g-3 summary-grid">
                            <div class="col-12 col-md-6">
                                <div class="summary-item h-100">
                                    <span>Average Order Value</span>
                                    <strong>{{ $formatMoney($dashboard['average_order_value']) }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="summary-item h-100">
                                    <span>Units On Hand</span>
                                    <strong>{{ number_format($dashboard['units_on_hand']) }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="summary-item h-100">
                                    <span>Quotes / Drafts / Suspended</span>
                                    <strong>{{ number_format($dashboard['open_quote_count']) }} / {{ number_format($dashboard['draft_count']) }} / {{ number_format($dashboard['suspend_count']) }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="summary-item h-100">
                                    <span>Stock In / Out {{ $periodLabel }}</span>
                                    <strong>{{ number_format($dashboard['stock_in_this_month']) }} / {{ number_format($dashboard['stock_out_this_month']) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-5">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h5 class="dashboard-card-title">Catalog Snapshot</h5>
                            <p>A quick read on assortment, reach, and delivery coverage.</p>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        <div class="row g-3 spotlight-list">
                            <div class="col-12">
                                <div class="spotlight-item">
                                    <div>
                                        <strong>{{ number_format($dashboard['active_products']) }} Products</strong>
                                        <span>{{ number_format($dashboard['variant_products']) }} with variants</span>
                                    </div>
                                    <small>{{ number_format($dashboard['total_skus']) }} SKUs</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="spotlight-item">
                                    <div>
                                        <strong>{{ number_format($dashboard['total_customers']) }} Customers</strong>
                                        <span>Active customer base</span>
                                    </div>
                                    <small>{{ number_format($dashboard['new_customers_this_month']) }} joined in {{ $periodLabel }}</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="spotlight-item">
                                    <div>
                                        <strong>{{ number_format($dashboard['active_shipment_zones']) }} Shipment Zones</strong>
                                        <span>Delivery coverage configured</span>
                                    </div>
                                    <small>{{ $scopeLabel }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h5 class="dashboard-card-title">Top Selling Products</h5>
                            <p>Highest performing products by units and revenue.</p>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        @if($topProducts->isEmpty())
                        <p class="text-muted mb-0">No finalized sales yet.</p>
                        @else
                        <div class="table-responsive p-0">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Units</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td class="text-end">{{ number_format($product->units_sold) }}</td>
                                        <td class="text-end">{{ $formatMoney($product->revenue) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-6">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h5 class="dashboard-card-title">Low Stock Alert</h5>
                            <p>Products currently approaching or below their alert threshold.</p>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        @if($lowStockItems->isEmpty())
                        <p class="text-muted mb-0">No low stock items right now.</p>
                        @else
                        <div class="table-responsive p-0">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th class="text-end">Stock</th>
                                        <th class="text-end">Alert</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockItems as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ $item->product_code }}</td>
                                        <td class="text-end">{{ number_format($item->current_stock) }}</td>
                                        <td class="text-end">{{ number_format($item->alert_level) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-6">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h5 class="dashboard-card-title">Recent Orders</h5>
                            <p>Latest order activity with payment and fulfillment status in {{ $periodLabel }}.</p>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        @if($recentOrders->isEmpty())
                        <p class="text-muted mb-0">No orders yet.</p>
                        @else
                        {{-- Desktop table --}}
                        <div class="table-responsive d-none d-md-block">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                    @php $due = max((float) ($order->due_total ?? ((float) $order->grand_total - (float) $order->paid_amount)), 0); @endphp
                                    <tr>
                                        <td><a href="{{ route('order.details', $order->id) }}">#{{ $order->id }}</a></td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>
                                            <span class="status-pill">
                                                {{ ucfirst($order->payment_last_status) }} / {{ ucfirst($order->order_last_status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ $formatMoney($order->grand_total) }}</td>
                                        <td class="text-end">{{ $formatMoney($due) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile card list --}}
                        <div class="d-md-none order-card-list">
                            @foreach($recentOrders as $order)
                            @php $due = max((float) ($order->due_total ?? ((float) $order->grand_total - (float) $order->paid_amount)), 0); @endphp
                            <div class="order-mobile-card">
                                <div class="order-mobile-card__top">
                                    <a href="{{ route('order.details', $order->id) }}" class="order-mobile-card__id">
                                        #{{ $order->id }}
                                    </a>
                                    <span class="status-pill">
                                        {{ ucfirst($order->payment_last_status) }} / {{ ucfirst($order->order_last_status) }}
                                    </span>
                                </div>
                                <div class="order-mobile-card__customer">{{ $order->customer_name }}</div>
                                <div class="order-mobile-card__amounts">
                                    <div class="order-mobile-card__amount-item">
                                        <span class="order-mobile-card__amount-label">Total</span>
                                        <span class="order-mobile-card__amount-value">{{ $formatMoney($order->grand_total) }}</span>
                                    </div>
                                    <div class="order-mobile-card__amount-item {{ $due > 0 ? 'order-mobile-card__amount-item--due' : '' }}">
                                        <span class="order-mobile-card__amount-label">Due</span>
                                        <span class="order-mobile-card__amount-value">{{ $formatMoney($due) }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-6">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h5 class="dashboard-card-title">Shipment Zone Performance</h5>
                            <p>Revenue contribution and usage by delivery zone.</p>
                        </div>
                    </div>
                    <div class="dashboard-card-body">
                        @if($shipmentZonePerformance->isEmpty())
                        <p class="text-muted mb-0">No shipment zone usage recorded yet.</p>
                        @else
                        <div class="table-responsive">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Zone</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shipmentZonePerformance as $zone)
                                    <tr>
                                        <td>{{ $zone->zone_name }}</td>
                                        <td class="text-end">{{ number_format($zone->order_count) }}</td>
                                        <td class="text-end">{{ $formatMoney($zone->revenue) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </article>
            </div>
        </div>
    </section>
</div>
@endsection
