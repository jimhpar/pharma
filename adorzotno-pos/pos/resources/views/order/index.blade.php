@extends('layouts.main')
@php
    $user = auth()->user();
    $canManageOrders = $user?->hasRoleSlug('super-admin') || $user?->hasPermissionSlug('orders.manage');
@endphp
@section('main.content')
<style>
    .order-list-card {
        border: 1px solid #e3ebf3;
        border-radius: 1rem;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .order-list-card .card-header {
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
        border-bottom: 1px solid #e3ebf3;
    }

    .order-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    #orderTable {
        width: 100% !important;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    #orderTable thead th {
        background: #f4f8fc;
        color: #40546a;
        border-bottom: 1px solid #dbe6f1;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 0.72rem 0.6rem;
        white-space: nowrap;
    }

    #orderTable tbody td {
        padding: 0.62rem 0.6rem;
        border-bottom: 1px solid #edf2f7;
        color: #52677d;
        vertical-align: middle;
        overflow: hidden;
    }

    #orderTable tbody tr:hover td {
        background: #f9fbfd;
    }

    .order-sl {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 0.45rem;
        background: #f1f5f9;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .order-invoice {
        color: #102a43;
        font-size: 0.8rem;
        font-weight: 900;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .order-main-text {
        color: #17324d;
        font-size: 0.8rem;
        font-weight: 800;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .order-sub-text {
        color: #71859b;
        font-size: 0.66rem;
        font-weight: 600;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .order-total {
        color: #0f766e;
        font-size: 0.9rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .order-due-text {
        margin-top: 0.08rem;
        color: #b45309;
        font-size: 0.65rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .order-badge-stack {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.2rem;
    }

    .order-status-badge {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        border-radius: 0.35rem;
        padding: 0.22rem 0.45rem;
        font-size: 0.62rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .order-status-badge.status-main { background: #eef4ff; color: #1e40af; }
    .order-status-badge.status-pay { background: #eefcf3; color: #166534; }

    .order-action-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
    }

    .order-action-btn {
        width: 1.95rem;
        height: 1.95rem;
        border: 0;
        border-radius: 0.45rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff !important;
        box-shadow: none;
        cursor: pointer;
    }

    .order-action-btn.view { background: #2563eb; }
    .order-action-btn.edit { background: #64748b; }

    #orderTable col.order-col-sl { width: 5%; }
    #orderTable col.order-col-id { width: 16%; }
    #orderTable col.order-col-date { width: 11%; }
    #orderTable col.order-col-customer { width: 16%; }
    #orderTable col.order-col-store { width: 14%; }
    #orderTable col.order-col-type { width: 10%; }
    #orderTable col.order-col-total { width: 13%; }
    #orderTable col.order-col-status { width: 10%; }
    #orderTable col.order-col-action { width: 7%; }
</style>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Orders</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Orders</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card order-list-card">
            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <h5 class="card-title mb-0">Order List</h5>
                <div class="row g-2 w-100 justify-content-lg-end">
                    <div class="col-12 col-lg-3">
                        <input
                            type="text"
                            id="orderSearch"
                            class="form-control"
                            placeholder="Search by order, invoice, customer"
                        >
                    </div>
                    <div class="col-6 col-lg-2">
                        <input type="date" id="startDateFilter" class="form-control">
                    </div>
                    <div class="col-6 col-lg-2">
                        <input type="date" id="endDateFilter" class="form-control">
                    </div>
                    <div class="col-6 col-lg-2">
                        <select id="branchFilter" class="form-select">
                            <option value="">All Store</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <select id="cashierFilter" class="form-select">
                            <option value="">All Cashier</option>
                            @foreach ($cashiers as $cashier)
                                <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <select id="orderStatusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <select id="paymentStatusFilter" class="form-select">
                            <option value="">All Payment</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <select id="fulfillmentStatusFilter" class="form-select">
                            <option value="">All Fulfillment</option>
                            <option value="unfulfilled">Unfulfilled</option>
                            <option value="partial">Partial</option>
                            <option value="fulfilled">Fulfilled</option>
                            <option value="returned">Returned</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <select id="saleTypeFilter" class="form-select">
                            <option value="">All Sale Type</option>
                            @foreach ($saleTypes as $saleType)
                                <option value="{{ $saleType }}">{{ $saleType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <select id="salesChannelFilter" class="form-select">
                            <option value="">All Channel</option>
                            @foreach ($salesChannels as $channel => $label)
                                <option value="{{ $channel }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <input type="number" min="0" step="0.01" id="minTotalFilter" class="form-control" placeholder="Min total">
                    </div>
                    <div class="col-6 col-lg-2">
                        <input type="number" min="0" step="0.01" id="maxTotalFilter" class="form-control" placeholder="Max total">
                    </div>
                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <button type="button" id="resetOrderFilters" class="btn btn-light w-100">Reset</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="order-table-wrap">
                    <table id="orderTable" class="table align-middle mb-0">
                        <colgroup>
                            <col class="order-col-sl">
                            <col class="order-col-id">
                            <col class="order-col-date">
                            <col class="order-col-customer">
                            <col class="order-col-store">
                            <col class="order-col-type">
                            <col class="order-col-total">
                            <col class="order-col-status">
                            <col class="order-col-action">
                        </colgroup>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <div id="statusModal"></div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    let searchDebounceTimer;
    const escapeHtml = function (value) {
        return $('<div>').text(value || '').html();
    };
    const formatOrderMoney = function (value) {
        const amount = Number(value || 0);
        return 'BDT ' + amount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    const orderTable = $('#orderTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        scrollX: false,
        dom: 'rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3"ip>',
        ajax: {
            url: "{{ route('order.list') }}",
            type: "POST",
            data: function (d) {
                d._token = "{{ csrf_token() }}";
                d.branch_id = $('#branchFilter').val();
                d.cashier_id = $('#cashierFilter').val();
                d.status = $('#orderStatusFilter').val();
                d.payment_status = $('#paymentStatusFilter').val();
                d.fulfillment_status = $('#fulfillmentStatusFilter').val();
                d.sale_type = $('#saleTypeFilter').val();
                d.sales_channel = $('#salesChannelFilter').val();
                d.start_date = $('#startDateFilter').val();
                d.end_date = $('#endDateFilter').val();
                d.min_total = $('#minTotalFilter').val();
                d.max_total = $('#maxTotalFilter').val();
            },
        },
        columns: [
            {
                title: 'SL',
                data: null,
                className: "text-center",
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return '<span class="order-sl">' + (meta.settings._iDisplayStart + meta.row + 1) + '</span>';
                }
            },
            {
                title: 'Invoice / Order',
                data: null,
                name: 'id',
                className: "text-start",
                orderable: true,
                searchable: true,
                render: function (data, type, row, meta) {
                    const invoiceNo = escapeHtml(data.invoice_no || 'No invoice');
                    const orderNo = escapeHtml(data.order_no || ('#' + data.id));
                    return '<div class="order-invoice" title="' + invoiceNo + '">' + invoiceNo + '</div>' +
                        '<div class="order-sub-text">Order: ' + orderNo + '</div>';
                }
            },
            { title: 'Date', data: 'order_date', name: 'order_date', className: "text-center", orderable: true, searchable: false },
            {
                title: 'Customer',
                data: 'customer',
                name: 'customer',
                className: "text-start",
                orderable: true,
                searchable: true,
                render: function (data) {
                    const customer = escapeHtml(data || 'Walk-in Customer');
                    return '<div class="order-main-text" title="' + customer + '">' + customer + '</div>';
                }
            },
            {
                title: 'Store / Cashier',
                data: null,
                name: 'branch_name',
                className: "text-start",
                orderable: true,
                searchable: true,
                render: function (data) {
                    const branchName = escapeHtml(data.branch_name || 'N/A');
                    const cashierName = escapeHtml(data.cashier_name || 'N/A');
                    return '<div class="order-main-text" title="' + branchName + '">' + branchName + '</div>' +
                        '<div class="order-sub-text" title="' + cashierName + '">' + cashierName + '</div>';
                }
            },
            {
                title: 'Type',
                data: null,
                name: 'sale_type',
                className: "text-center",
                orderable: false,
                searchable: true,
                render: function (data) {
                    return '<div class="order-main-text text-center">' + escapeHtml(data.sale_type || 'Sale') + '</div>' +
                        '<div class="order-sub-text text-center">' + escapeHtml(data.sales_channel_label || '') + '</div>';
                }
            },
            {
                title: 'Amount',
                data: 'grand_total',
                name: 'grand_total',
                className: "text-start",
                orderable: true,
                searchable: false,
                render: function (data, type, row) {
                    const dueAmount = Number(row.due_total || 0);
                    const itemsCount = Number(row.items_count || 0);
                    const paymentMethod = escapeHtml(row.payment_method || 'N/A');
                    const couponCodes = escapeHtml(row.coupon_codes || '');
                    const couponDiscount = Number(row.coupon_discount_total || 0);
                    return '<div class="order-total">' + escapeHtml(data) + '</div>' +
                        '<div class="order-sub-text">Items: ' + itemsCount + ' | ' + paymentMethod + '</div>' +
                        (couponCodes ? '<div class="order-sub-text">Coupon: ' + couponCodes + ' (-' + formatOrderMoney(couponDiscount) + ')</div>' : '') +
                        (dueAmount > 0 ? '<div class="order-due-text">Due ' + formatOrderMoney(dueAmount) + '</div>' : '<div class="order-sub-text">Paid</div>');
                }
            },
            {
                title: 'Status',
                data: null,
                name: 'status',
                className: "text-center",
                orderable: true,
                searchable: false,
                render: function (data) {
                    return '<div class="order-badge-stack">' +
                        '<span class="order-status-badge status-main">' + escapeHtml(data.order_last_status || 'N/A') + '</span>' +
                        '<span class="order-status-badge status-pay">' + escapeHtml(data.payment_last_status || 'N/A') + '</span>' +
                    '</div>';
                }
            },
            {
                title: 'Action',
                className: "text-center",
                data: function (data) {
                    let actions = '<div class="order-action-group"><button type="button" title="View" class="order-action-btn view" data-panel-id="' + data.id + '" onclick="viewOrder(this)"><i class="fa fa-eye"></i></button>';

                    @if($canManageOrders)
                    actions += '<button type="button" title="Status change" class="order-action-btn edit" data-panel-id="' + data.id + '" onclick="statusChange(this)"><i class="fas fa-edit"></i></button>';
                    @endif

                    return actions + '</div>';
                },
                orderable: false,
                searchable: false
            }
        ]
    });

    function applyOrderFilters() {
        orderTable.search($('#orderSearch').val()).draw();
    }

    $('#orderSearch').on('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function () {
            applyOrderFilters();
        }, 350);
    });

    $('#startDateFilter, #endDateFilter, #branchFilter, #cashierFilter, #orderStatusFilter, #paymentStatusFilter, #fulfillmentStatusFilter, #saleTypeFilter, #salesChannelFilter')
        .on('change', function () {
            applyOrderFilters();
        });

    $('#minTotalFilter, #maxTotalFilter').on('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function () {
            applyOrderFilters();
        }, 350);
    });

    $('#resetOrderFilters').on('click', function () {
        $('#orderSearch').val('');
        $('#startDateFilter').val('');
        $('#endDateFilter').val('');
        $('#branchFilter').val('');
        $('#cashierFilter').val('');
        $('#orderStatusFilter').val('');
        $('#paymentStatusFilter').val('');
        $('#fulfillmentStatusFilter').val('');
        $('#saleTypeFilter').val('');
        $('#salesChannelFilter').val('');
        $('#minTotalFilter').val('');
        $('#maxTotalFilter').val('');
        clearTimeout(searchDebounceTimer);
        orderTable.search('').draw();
    });
});

function viewOrder(x) {
    let btn = $(x).data('panel-id');
    let url = '{{ route("order.details", ":id") }}';
    window.location.href = url.replace(':id', btn);
}

function statusChange(element) {
    const orderId = $(element).data('panel-id');
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
        }
    });
}
</script>
@endsection
