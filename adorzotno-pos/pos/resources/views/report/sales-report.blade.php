@extends('layouts.main')
@section('main.content')
<style>
/* ── Stat cards ─────────────────────────── */
.sr-stat {
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid transparent;
}
.sr-stat .si {
    width: 46px; height: 46px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; flex-shrink: 0;
}
.sr-stat .sv { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
.sr-stat .sl { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-top: .2rem; }

.sr-s1 { background:#eff6ff; border-color:#bfdbfe; }
.sr-s1 .si { background:#dbeafe; color:#1d4ed8; }
.sr-s1 .sv { color:#1d4ed8; } .sr-s1 .sl { color:#3b82f6; }

.sr-s2 { background:#f0fdf4; border-color:#bbf7d0; }
.sr-s2 .si { background:#dcfce7; color:#15803d; }
.sr-s2 .sv { color:#15803d; } .sr-s2 .sl { color:#22c55e; }

.sr-s3 { background:#fff7ed; border-color:#fed7aa; }
.sr-s3 .si { background:#ffedd5; color:#c2410c; }
.sr-s3 .sv { color:#c2410c; font-size:1.1rem; } .sr-s3 .sl { color:#f97316; }

.sr-s4 { background:#fdf4ff; border-color:#e9d5ff; }
.sr-s4 .si { background:#f3e8ff; color:#7e22ce; }
.sr-s4 .sv { color:#7e22ce; font-size:1.1rem; } .sr-s4 .sl { color:#a855f7; }

/* ── Main card ──────────────────────────── */
.sr-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(15,23,42,.06);
    overflow: visible;
}
.sr-card .card-header {
    background: linear-gradient(135deg,#f8faff 0%,#eef3ff 100%);
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.25rem;
}
.sr-filter {
    background: #f8fafc;
    border-bottom: 1px solid #e8eef5;
    padding: 1rem 1.25rem;
}
.sr-filter-label {
    font-size: .67rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #475569;
    margin-bottom: .3rem;
}

/* ── Table ──────────────────────────────── */
.sr-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.sr-scroll::-webkit-scrollbar { height: 5px; }
.sr-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
.sr-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

#salesTable { min-width: 1000px; }

#salesTable thead th {
    background: #f0f4fa;
    color: #374151;
    font-size: .67rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 2px solid #d1d5db;
    padding: .7rem .75rem;
    white-space: nowrap;
}
#salesTable tbody td {
    padding: .65rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: .875rem;
    color: #374151;
}
#salesTable tbody tr:hover { background: #f8faff; }
#salesTable tbody tr:nth-child(even) { background: #fafbfd; }
#salesTable tbody tr:nth-child(even):hover { background: #f0f5ff; }

.sr-oid {
    display: inline-block;
    background: #1e293b;
    color: #fff;
    font-size: .72rem;
    font-weight: 800;
    border-radius: 5px;
    padding: 2px 8px;
    letter-spacing: .03em;
}
.sr-customer { font-weight: 700; color: #1e293b; }
.sr-branch   { font-size: .8rem; font-weight: 600; color: #475569; }
.sr-amount   { font-weight: 800; color: #0f766e; white-space: nowrap; }
.sr-del      { font-weight: 600; color: #64748b; white-space: nowrap; }
.sr-dis      { font-weight: 600; color: #dc2626; white-space: nowrap; }
.sr-date     { font-size: .8rem; font-weight: 700; color: #374151; white-space: nowrap; }
.sr-time     { font-size: .68rem; color: #94a3b8; margin-top: .05rem; white-space: nowrap; }

#salesTable tfoot th {
    background: #f0f4fa;
    font-size: .78rem;
    font-weight: 800;
    color: #1e293b;
    border-top: 2px solid #d1d5db;
    padding: .6rem .75rem;
}
.tfoot-label { font-size: .62rem; color: #94a3b8; font-weight: 600; display: block; }

#salesTable_wrapper .dataTables_info,
#salesTable_wrapper .dataTables_paginate {
    padding: .75rem 1.25rem;
}
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Sales Report</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sales Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- This Month Stat Cards --}}
    @php $monthLabel = now()->format('F Y'); @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="sr-stat sr-s1">
                <div class="si"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="sv">{{ number_format($thisMonth['count']) }}</div>
                    <div class="sl">Orders</div>
                    <div style="font-size:.62rem;color:#3b82f6;font-weight:700;margin-top:.1rem">
                        <i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="sr-stat sr-s2">
                <div class="si"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="sv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['revenue'], 0) }}</div>
                    <div class="sl">Revenue</div>
                    <div style="font-size:.62rem;color:#22c55e;font-weight:700;margin-top:.1rem">
                        <i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="sr-stat sr-s3">
                <div class="si"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="sv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['delivery'], 0) }}</div>
                    <div class="sl">Delivery</div>
                    <div style="font-size:.62rem;color:#f97316;font-weight:700;margin-top:.1rem">
                        <i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="sr-stat sr-s4">
                <div class="si"><i class="bi bi-tag"></i></div>
                <div>
                    <div class="sv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['discount'], 0) }}</div>
                    <div class="sl">Discounts</div>
                    <div style="font-size:.62rem;color:#a855f7;font-weight:700;margin-top:.1rem">
                        <i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card sr-card">

            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Sales Orders</h5>
                    <div class="text-muted small">Pending through completed orders with totals.</div>
                </div>
                <button type="button" class="btn btn-success fw-bold" onclick="downloadSalesExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            {{-- Filters --}}
            <div class="sr-filter">
                <div class="row g-2 align-items-end">
                    @if(!empty($branches) && $branches->isNotEmpty())
                    <div class="col-md-2 col-6">
                        <div class="sr-filter-label">Branch</div>
                        <select id="branchId" class="form-select form-select-sm sr-filter-input">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2 col-6">
                        <div class="sr-filter-label">Start Date</div>
                        <input type="date" id="startDate" class="form-control form-control-sm sr-filter-input">
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="sr-filter-label">End Date</div>
                        <input type="date" id="endDate" class="form-control form-control-sm sr-filter-input">
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="sr-filter-label">Order Status</div>
                        <select id="orderStatus" class="form-select form-select-sm sr-filter-input">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="packed">Packed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="sr-filter-label">Payment Status</div>
                        <select id="paymentStatus" class="form-select form-select-sm sr-filter-input">
                            <option value="">All Payments</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="sr-filter-label">Sales Channel</div>
                        <select id="salesChannel" class="form-select form-select-sm sr-filter-input">
                            <option value="">All Channels</option>
                            @foreach($salesChannels as $channel => $label)
                                <option value="{{ $channel }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1">
                        <button type="button" id="applyFilters" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel-fill me-1"></i>Apply
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-light btn-sm" style="min-width:38px">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body p-0">
                <div class="sr-scroll">
                    <table id="salesTable" class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Channel</th>
                                <th>Delivery</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Order Status</th>
                                <th>Payment</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end" style="color:#94a3b8;font-size:.7rem;font-weight:600">TOTALS</th>
                                <th id="delivery-total">
                                    <span class="tfoot-label">Delivery</span>
                                    <span>0.00</span>
                                </th>
                                <th id="orderDiscount-total">
                                    <span class="tfoot-label">Discount</span>
                                    <span>0.00</span>
                                </th>
                                <th id="sales-total">
                                    <span class="tfoot-label">Revenue</span>
                                    <span>0.00</span>
                                </th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
var dataTable;

$(document).ready(function () {
    dataTable = $('#salesTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("sales.list") }}',
            type: 'POST',
            data: function (d) {
                d._token        = '{{ csrf_token() }}';
                d.startDate     = $('#startDate').val();
                d.endDate       = $('#endDate').val();
                d.paymentStatus = $('#paymentStatus').val();
                d.orderStatus   = $('#orderStatus').val();
                d.salesChannel  = $('#salesChannel').val();
                d.branch_id     = $('#branchId').val();
            },
            dataSrc: function (json) {
                var fmt = function (n) {
                    return '৳ ' + Number(n || 0).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                };
                var fmtPlain = function (n) {
                    return Number(n || 0).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                };

                $('#stat-orders').text(json.orderCount ?? 0);
                $('#stat-revenue').text(fmt(json.orderTotalSum));
                $('#stat-delivery').text(fmt(json.deliveryChargeSum));
                $('#stat-discount').text(fmt(json.orderDiscountSum));

                $('#delivery-total span:last').text(fmtPlain(json.deliveryChargeSum));
                $('#orderDiscount-total span:last').text(fmtPlain(json.orderDiscountSum));
                $('#sales-total span:last').text(fmtPlain(json.orderTotalSum));

                return json.data;
            },
        },
        columns: [
            {
                title: 'Order ID', data: 'id', name: 'id',
                className: 'text-center', orderable: true, searchable: false, width: '80px',
                render: function (data) {
                    return '<span class="sr-oid">#' + data + '</span>';
                }
            },
            {
                title: 'Date', data: 'order_date', name: 'order_date',
                className: 'text-start', orderable: false, searchable: false, width: '100px',
                render: function (data) {
                    if (!data) return '<span class="sr-time">—</span>';
                    var p = data.split('|');
                    return '<div class="sr-date">' + (p[0]||data) + '</div>'
                         + '<div class="sr-time">' + (p[1]||'') + '</div>';
                }
            },
            {
                title: 'Customer', data: 'customer', name: 'customer',
                className: 'text-start', orderable: false, searchable: true, width: '140px',
                render: function (data) {
                    return '<span class="sr-customer">' + data + '</span>';
                }
            },
            {
                title: 'Branch', data: 'branch_name', name: 'branch_name',
                className: 'text-start', orderable: false, searchable: false, width: '110px',
                render: function (data) {
                    return '<span class="sr-branch">' + data + '</span>';
                }
            },
            {
                title: 'Channel', data: 'sales_channel_badge', name: 'sales_channel',
                className: 'text-center', orderable: false, searchable: false, width: '90px',
            },
            {
                title: 'Delivery', data: 'delivery_fee', name: 'delivery_fee',
                className: 'text-end', orderable: false, searchable: false, width: '90px',
                render: function (data) { return '<span class="sr-del">' + data + '</span>'; }
            },
            {
                title: 'Discount', data: 'order_discount', name: 'order_discount',
                className: 'text-end', orderable: false, searchable: false, width: '90px',
                render: function (data) { return '<span class="sr-dis">' + data + '</span>'; }
            },
            {
                title: 'Total', data: 'grand_total', name: 'grand_total',
                className: 'text-end', orderable: true, searchable: false, width: '100px',
                render: function (data) { return '<span class="sr-amount">' + data + '</span>'; }
            },
            {
                title: 'Order Status', data: 'order_last_status', name: 'order_last_status',
                className: 'text-center', orderable: false, searchable: false, width: '105px',
            },
            {
                title: 'Payment', data: 'payment_last_status', name: 'payment_last_status',
                className: 'text-center', orderable: false, searchable: false, width: '85px',
            },
            {
                title: '', data: null,
                className: 'text-center', orderable: false, searchable: false, width: '50px',
                render: function (data) {
                    return '<a class="btn btn-sm btn-outline-primary px-2 py-1" onclick="viewOrder(' + data.id + ')" title="View Order">'
                        + '<i class="bi bi-eye"></i></a>';
                }
            },
        ],
        language: {
            processing: '<div class="text-primary fw-bold py-3"><i class="bi bi-arrow-repeat me-1"></i> Loading...</div>',
            emptyTable:  '<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No sales records found</div>',
            zeroRecords: '<div class="text-center py-4 text-muted"><i class="bi bi-search fs-3 d-block mb-2"></i>No matching orders</div>',
        },
    });

    $('.sr-filter-input').on('change', function () { dataTable.ajax.reload(); });
    $('#applyFilters').on('click', function () { dataTable.ajax.reload(); });

    $('#resetFilters').on('click', function () {
        $('#branchId, #orderStatus, #paymentStatus, #salesChannel').val('');
        $('#startDate, #endDate').val('');
        dataTable.ajax.reload();
    });
});

function downloadSalesExcel() {
    var p = new URLSearchParams({
        startDate:     $('#startDate').val()     || '',
        endDate:       $('#endDate').val()       || '',
        paymentStatus: $('#paymentStatus').val() || '',
        orderStatus:   $('#orderStatus').val()   || '',
        salesChannel:  $('#salesChannel').val()  || '',
        branch_id:     $('#branchId').val()      || '',
    });
    window.location.href = '{{ route("sales.excel") }}?' + p.toString();
}

function viewOrder(id) {
    var url = '{{ route("order.details", ":id") }}';
    window.location.href = url.replace(':id', id);
}
</script>
@endsection
