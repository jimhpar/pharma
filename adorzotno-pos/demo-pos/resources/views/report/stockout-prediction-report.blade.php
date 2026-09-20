@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
#stockoutTable_wrapper .dataTables_info,
#stockoutTable_wrapper .dataTables_paginate { padding:.7rem 1.25rem; }
.risk-bar { display:flex; gap:4px; margin-top:.5rem; }
.risk-seg { height:6px; border-radius:3px; flex:1; }
.risk-critical-bg { background:#fee2e2; border-color:#fca5a5; }
.risk-critical-bg .rsi { background:#fecaca; color:#dc2626; }
.risk-critical-bg .rsv { color:#dc2626; }
.risk-critical-bg .rsl { color:#dc2626; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Stockout Prediction <small class="text-muted fs-6">AI-powered forecast</small></h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Stockout Prediction</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- Summary stat cards --}}
    @php
        $liveLabel = '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Last 30 days</div>';
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c5" style="border-color:#fca5a5;background:#fef2f2">
                <div class="rsi" style="background:#fee2e2;color:#dc2626"><i class="bi bi-fire"></i></div>
                <div>
                    <div class="rsv" style="color:#dc2626">{{ number_format($stats['critical']) }}</div>
                    <div class="rsl" style="color:#dc2626">Critical (&le;7 days)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['high']) }}</div>
                    <div class="rsl">High (&le;14 days)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat" style="background:#fffbeb;border-color:#fde68a">
                <div class="rsi" style="background:#fef3c7;color:#d97706"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="rsv" style="color:#d97706">{{ number_format($stats['medium']) }}</div>
                    <div class="rsl" style="color:#d97706">Medium (&le;30 days)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-shield-check"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['safe']) }}</div>
                    <div class="rsl">Safe (&gt;30 days)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Info banner --}}
    <div class="alert mb-3 py-2 px-3 d-flex align-items-start gap-2" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;font-size:.82rem;color:#1e40af">
        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
        <span>
            <strong>How predictions work:</strong> Average daily sales velocity is calculated from your completed orders over the selected period.
            Days remaining = Current Stock &divide; Avg Daily Sales. Products with <strong>no recent sales</strong> are excluded from the risk gauge but still visible with "No Data" status.
        </span>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title"><i class="bi bi-graph-down-arrow me-2 text-danger"></i>Predicted Stockout Timeline</div>
                    <div class="rpt-head-sub">Products ranked by urgency &mdash; critical items appear first.</div>
                </div>
                <button class="btn btn-success fw-bold" onclick="downloadExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    @if(!empty($branches) && $branches->isNotEmpty())
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Branch</span>
                        <select id="branchId" class="form-select form-select-sm rfilter">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Warehouse</span>
                        <select id="warehouseId" class="form-select form-select-sm rfilter">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Category</span>
                        <select id="categoryId" class="form-select form-select-sm rfilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Risk Level</span>
                        <select id="riskLevel" class="form-select form-select-sm rfilter">
                            <option value="">All Levels</option>
                            <option value="critical">&#128308; Critical (&le;7d)</option>
                            <option value="high">&#128992; High (&le;14d)</option>
                            <option value="medium">&#128993; Medium (&le;30d)</option>
                            <option value="safe">&#128994; Safe (&gt;30d)</option>
                            <option value="no_data">&#9898; No Sales Data</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Analysis Period</span>
                        <select id="period" class="form-select form-select-sm rfilter">
                            <option value="30" selected>Last 30 days</option>
                            <option value="60">Last 60 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1">
                        <button id="applyBtn" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill me-1"></i>Apply</button>
                        <button id="resetBtn" class="btn btn-light btn-sm" style="min-width:36px"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="rpt-scroll">
                    <table id="stockoutTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>Category</th>
                            <th>Branch / Warehouse</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Sold (Period)</th>
                            <th class="text-center">Avg / Day</th>
                            <th class="text-center">Days Left</th>
                            <th class="text-center">Stockout Date</th>
                            <th class="text-center">Risk</th>
                        </tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
var dt = null;

var COLS = [
    {
        data: 'sku_code', searchable: true, orderable: true, width: '90px',
        render: function (d) { return '<span class="rpt-sku">' + d + '</span>'; }
    },
    {
        data: 'product_name', searchable: true, orderable: true, width: '160px',
        render: function (d) { return '<span class="rpt-bold">' + d + '</span>'; }
    },
    {
        data: 'variant', searchable: false, orderable: false, width: '85px',
        render: function (d) {
            return d === 'Single'
                ? '<span class="text-muted small">—</span>'
                : '<span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.68rem">' + d + '</span>';
        }
    },
    {
        data: 'category', searchable: false, orderable: true, width: '100px',
        render: function (d) { return '<span style="font-size:.8rem;color:#64748b">' + d + '</span>'; }
    },
    {
        data: 'branch', searchable: false, orderable: true, width: '120px',
        render: function (d, type, row) {
            return '<span style="font-size:.78rem;font-weight:700;color:#1e293b">' + d + '</span>'
                + '<div style="font-size:.7rem;color:#94a3b8">' + row.warehouse + '</div>';
        }
    },
    {
        data: 'current_stock', className: 'text-center', orderable: true, searchable: false, width: '80px',
        render: function (d) { return '<span style="font-weight:800;color:#0f172a">' + d + '</span>'; }
    },
    {
        data: 'total_sold', className: 'text-center', orderable: true, searchable: false, width: '85px',
        render: function (d) { return '<span style="font-weight:700;color:#6366f1">' + d + '</span>'; }
    },
    {
        data: 'avg_daily_sales', className: 'text-center', orderable: true, searchable: false, width: '80px',
        render: function (d) {
            if (!d || d == 0) return '<span class="text-muted small">—</span>';
            return '<span style="font-weight:700;color:#0891b2">' + d + '</span>';
        }
    },
    {
        /* sort by days_sort (numeric), display using days_remaining */
        data: 'days_sort', className: 'text-center', orderable: true, searchable: false, width: '80px',
        render: function (d, type, row) {
            if (type === 'sort' || type === 'type') return d;
            var dr = row.days_remaining;
            if (dr === null || dr === undefined) return '<span class="text-muted small">—</span>';
            var color = dr <= 7 ? '#dc2626' : dr <= 14 ? '#ea580c' : dr <= 30 ? '#d97706' : '#15803d';
            return '<span style="font-weight:900;color:' + color + ';font-size:.95rem">' + dr + 'd</span>';
        }
    },
    {
        data: 'stockout_date', className: 'text-center', orderable: true, searchable: false, width: '105px',
        render: function (d) {
            if (!d) return '<span class="text-muted small">N/A</span>';
            return '<span style="font-size:.78rem;font-weight:700;color:#374151;background:#f1f5f9;padding:2px 7px;border-radius:5px">' + d + '</span>';
        }
    },
    {
        data: 'risk_level', className: 'text-center', orderable: false, searchable: false, width: '110px',
        render: function (d) {
            var cfg = {
                critical: ['#fee2e2', '#991b1b', '&#128308; Critical'],
                high:     ['#fff7ed', '#c2410c', '&#128992; High'],
                medium:   ['#fef3c7', '#92400e', '&#128993; Medium'],
                safe:     ['#d1fae5', '#065f46', '&#128994; Safe'],
                no_data:  ['#f1f5f9', '#64748b', '&#9898; No Data'],
            };
            var c = cfg[d] || cfg.no_data;
            return '<span style="background:' + c[0] + ';color:' + c[1] + ';font-size:.67rem;font-weight:800;padding:3px 10px;border-radius:6px;white-space:nowrap">' + c[2] + '</span>';
        }
    },
];

function loadTable() {
    if (dt) { dt.destroy(); $('#stockoutTable tbody').empty(); }

    dt = $('#stockoutTable').DataTable({
        processing: true,
        serverSide: false,
        autoWidth: false,
        stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.stockout.list") }}',
            type: 'POST',
            data: {
                _token:       '{{ csrf_token() }}',
                branch_id:    $('#branchId').val()    || '',
                warehouse_id: $('#warehouseId').val() || '',
                category_id:  $('#categoryId').val()  || '',
                risk_level:   $('#riskLevel').val()   || '',
                period:       $('#period').val()      || '30',
            },
            dataSrc: 'data'
        },
        columns: COLS,
        language: {
            emptyTable: '<div class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-2 d-block mb-2" style="color:#22c55e"></i>No stockout risk detected for the selected filters.</div>',
            processing: '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Calculating predictions...</div>'
        },
        order: [[8, 'asc']]   /* sort by days_sort column asc — nulls (999999) go last */
    });
}

$(document).ready(function () {
    loadTable();

    $('#applyBtn').on('click', loadTable);
    $('.rfilter').on('change', loadTable);
    $('#resetBtn').on('click', function () {
        $('#branchId, #warehouseId, #categoryId, #riskLevel').val('');
        $('#period').val('30');
        loadTable();
    });
});

function downloadExcel() {
    var p = new URLSearchParams({
        branch_id:    $('#branchId').val()    || '',
        warehouse_id: $('#warehouseId').val() || '',
        category_id:  $('#categoryId').val()  || '',
        risk_level:   $('#riskLevel').val()   || '',
        period:       $('#period').val()      || '30',
    });
    window.location.href = '{{ route("report.stockout.excel") }}?' + p.toString();
}
</script>
@endsection
