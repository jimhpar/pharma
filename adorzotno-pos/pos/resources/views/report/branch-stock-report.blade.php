@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
#branchStockTable_wrapper .dataTables_info,
#branchStockTable_wrapper .dataTables_paginate { padding:.7rem 1.25rem; }
.branch-bar { height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;margin-top:.35rem; }
.branch-bar-fill { height:100%;border-radius:4px;background:linear-gradient(90deg,#3b82f6,#8b5cf6); }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Branch Stock Summary</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Branch Stock Summary</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $liveLabel = '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>'; @endphp

    {{-- Summary stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-building"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['branches']) }}</div>
                    <div class="rsl">Branches with Stock</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-box-seam-fill"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['sku_count']) }}</div>
                    <div class="rsl">Total SKUs (system-wide)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c4">
                <div class="rsi"><i class="bi bi-stack"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['total_qty']) }}</div>
                    <div class="rsl">Total Quantity</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-currency-exchange"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.05rem;color:#c2410c">৳ {{ number_format($stats['total_value'], 0) }}</div>
                    <div class="rsl">Total Stock Value</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Stock by Branch & Warehouse</div>
                    <div class="rpt-head-sub">কোন branch-এ কতটা product আছে এবং তার মোট মূল্য।</div>
                </div>
                <button class="btn btn-success fw-bold" onclick="downloadExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    @if($branches->isNotEmpty())
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Branch</span>
                        <select id="branchId" class="form-select form-select-sm rfilter">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Warehouse</span>
                        <select id="warehouseId" class="form-select form-select-sm rfilter">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Category</span>
                        <select id="categoryId" class="form-select form-select-sm rfilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1 align-items-end">
                        <button id="applyBtn" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill me-1"></i>Apply</button>
                        <button id="resetBtn" class="btn btn-light btn-sm" style="min-width:36px"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="rpt-scroll">
                    <table id="branchStockTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Branch</th>
                            <th>Warehouse</th>
                            <th class="text-center">SKU Count</th>
                            <th class="text-center">Total Qty</th>
                            <th class="text-end">Stock Value</th>
                            <th>Value Share</th>
                        </tr></thead>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total</th>
                                <th class="text-center" id="ftSkuCount">—</th>
                                <th class="text-center" id="ftTotalQty">—</th>
                                <th class="text-end" id="ftTotalValue">—</th>
                                <th></th>
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
var dt, grandValue = 0;

$(document).ready(function () {
    dt = $('#branchStockTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.branchStock.list") }}', type: 'POST',
            data: function (d) {
                d._token       = '{{ csrf_token() }}';
                d.branch_id    = $('#branchId').val();
                d.warehouse_id = $('#warehouseId').val();
                d.category_id  = $('#categoryId').val();
            },
            dataSrc: function (json) {
                // Compute grand total value for bar chart
                grandValue = 0;
                json.data.forEach(function (r) { grandValue += (r.raw_value || 0); });
                // Footer totals
                var totalSku = json.data.reduce(function(s, r){ return s + (r.sku_count||0); }, 0);
                var totalQty = json.data.reduce(function(s, r){ return s + (r.total_qty||0); }, 0);
                $('#ftSkuCount').text(totalSku.toLocaleString());
                $('#ftTotalQty').text(totalQty.toLocaleString());
                $('#ftTotalValue').html('<span style="font-weight:800;color:#c2410c">৳ ' + grandValue.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>');
                return json.data;
            }
        },
        columns: [
            {
                data: 'branch_name', name: 'branch_name', orderable: false, searchable: false, width: '160px',
                render: function (d) {
                    return '<div style="display:flex;align-items:center;gap:.5rem">'
                         + '<i class="bi bi-building-fill" style="color:#3b82f6;font-size:.9rem"></i>'
                         + '<span class="rpt-bold" style="font-size:.88rem">' + d + '</span></div>';
                }
            },
            {
                data: 'warehouse_name', name: 'warehouse_name', orderable: false, searchable: false, width: '140px',
                render: function (d) {
                    return '<span style="font-size:.82rem;color:#64748b"><i class="bi bi-archive me-1" style="font-size:.72rem"></i>' + d + '</span>';
                }
            },
            {
                data: 'sku_count', name: 'sku_count', className: 'text-center', orderable: true, searchable: false, width: '100px',
                render: function (d) {
                    return '<span style="background:#dbeafe;color:#1e40af;font-size:.88rem;font-weight:800;padding:3px 12px;border-radius:20px">' + Number(d).toLocaleString() + '</span>';
                }
            },
            {
                data: 'total_qty', name: 'total_qty', className: 'text-center', orderable: true, searchable: false, width: '110px',
                render: function (d) {
                    return '<span style="background:#dcfce7;color:#15803d;font-size:.88rem;font-weight:800;padding:3px 12px;border-radius:20px">' + Number(d).toLocaleString() + '</span>';
                }
            },
            {
                data: 'total_value', name: 'total_value', className: 'text-end', orderable: false, searchable: false, width: '130px',
                render: function (d) {
                    return '<span style="font-weight:800;color:#7c3aed;font-size:.9rem">' + d + '</span>';
                }
            },
            {
                data: 'raw_value', name: 'raw_value', orderable: false, searchable: false, width: '160px',
                render: function (d) {
                    var pct = grandValue > 0 ? Math.min(100, (d / grandValue) * 100) : 0;
                    return '<div style="font-size:.72rem;color:#64748b;font-weight:700">' + pct.toFixed(1) + '% of total</div>'
                         + '<div class="branch-bar"><div class="branch-bar-fill" style="width:' + pct + '%"></div></div>';
                }
            },
        ],
        language: {
            emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-building fs-3 d-block mb-2"></i>No stock data found.</div>'
        },
        order: [[4, 'desc']]
    });

    $('#applyBtn').on('click', function () { dt.ajax.reload(); });
    $('.rfilter').on('change', function () { dt.ajax.reload(); });
    $('#resetBtn').on('click', function () {
        $('#branchId, #warehouseId, #categoryId').val('');
        dt.ajax.reload();
    });
});

function downloadExcel() {
    var p = new URLSearchParams({
        branch_id:    $('#branchId').val()    || '',
        warehouse_id: $('#warehouseId').val() || '',
        category_id:  $('#categoryId').val()  || '',
    });
    window.location.href = '{{ route("report.branchStock.excel") }}?' + p.toString();
}
</script>
@endsection
