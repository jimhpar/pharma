@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
#cartonBoxTable_wrapper .dataTables_info,
#cartonBoxTable_wrapper .dataTables_paginate { padding:.7rem 1.25rem; }
.box-dot { display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:3px; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Carton / Box Tracking</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Carton / Box Tracking</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $live = '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live</div>'; @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-box2-fill"></i></div>
                <div><div class="rsv">{{ number_format($stats['cartons']) }}</div><div class="rsl">Cartons</div>{!! $live !!}</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="rpt-stat rpt-c4">
                <div class="rsi"><i class="bi bi-archive-fill"></i></div>
                <div><div class="rsv">{{ number_format($stats['boxes_total']) }}</div><div class="rsl">Total Boxes</div>{!! $live !!}</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="rpt-stat rpt-c1">
                <div class="rsi" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-lock-fill"></i></div>
                <div><div class="rsv" style="color:#1d4ed8">{{ number_format($stats['boxes_sealed']) }}</div><div class="rsl">Sealed</div>{!! $live !!}</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="rpt-stat" style="background:#fffbeb;border-color:#fde68a">
                <div class="rsi" style="background:#fef3c7;color:#d97706"><i class="bi bi-box-arrow-up-right"></i></div>
                <div><div class="rsv" style="color:#d97706">{{ number_format($stats['boxes_open']) }}</div><div class="rsl">Open</div>{!! $live !!}</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="rpt-stat" style="background:#f9fafb;border-color:#e5e7eb">
                <div class="rsi" style="background:#f3f4f6;color:#9ca3af"><i class="bi bi-inbox"></i></div>
                <div><div class="rsv" style="color:#9ca3af">{{ number_format($stats['boxes_empty']) }}</div><div class="rsl">Empty</div>{!! $live !!}</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-stack"></i></div>
                <div><div class="rsv">{{ number_format($stats['units_available']) }}</div><div class="rsl">Units Available</div>{!! $live !!}</div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Carton-level Stock Tracker</div>
                    <div class="rpt-head-sub">প্রতিটি carton-এ কতটা box আছে, কোনটা open/sealed/empty, এবং কত unit বাকি।</div>
                </div>
                <button class="btn btn-success fw-bold" onclick="downloadExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
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
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Status</span>
                        <select id="statusFilter" class="form-select form-select-sm rfilter">
                            <option value="">All</option>
                            <option value="active">Has Stock</option>
                            <option value="empty">Empty</option>
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
                    <table id="cartonBoxTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Carton Code</th>
                            <th>Product / SKU</th>
                            <th>Warehouse</th>
                            <th class="text-center">Config (Box×Unit)</th>
                            <th class="text-end">Unit Cost</th>
                            <th>Batch No</th>
                            <th class="text-center">Expiry</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">Sold</th>
                            <th class="text-center">Boxes</th>
                            <th class="text-center">Status</th>
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
var dt;
$(document).ready(function () {
    dt = $('#cartonBoxTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.cartonBox.list") }}', type: 'POST',
            data: function (d) {
                d._token      = '{{ csrf_token() }}';
                d.warehouse_id = $('#warehouseId').val();
                d.category_id  = $('#categoryId').val();
                d.status       = $('#statusFilter').val();
            }
        },
        columns: [
            {
                data: 'carton_code', name: 'carton_code', orderable: false, searchable: true, width: '130px',
                render: function (d) {
                    return '<span style="background:#1d4ed8;color:#fff;font-size:.7rem;font-weight:800;border-radius:5px;padding:2px 8px;letter-spacing:.02em">' + d + '</span>';
                }
            },
            {
                data: 'product', name: 'product', orderable: false, searchable: true, width: '170px',
                render: function (d, t, row) {
                    return '<div class="rpt-bold" style="font-size:.84rem">' + d + '</div>'
                         + '<div style="font-size:.68rem;background:#1d4ed8;color:#fff;border-radius:4px;padding:1px 6px;display:inline-block;margin-top:.15rem">' + row.sku_code + '</div>';
                }
            },
            { data: 'warehouse', name: 'warehouse', orderable: false, searchable: false, width: '100px',
              render: function(d){ return '<span style="font-size:.8rem;color:#64748b">'+d+'</span>'; } },
            {
                data: 'config', name: 'config', className: 'text-center', orderable: false, searchable: false, width: '110px',
                render: function(d, t, row){
                    return '<div style="font-size:.8rem;font-weight:700;color:#374151">'+d+'</div>'
                         + '<div style="font-size:.68rem;color:#94a3b8">'+row.total_units+' units total</div>';
                }
            },
            {
                data: 'unit_cost', name: 'unit_cost', className: 'text-end', orderable: false, searchable: false, width: '90px',
                render: function(d){ return d !== '—' ? '<span style="font-weight:700;color:#0f766e">'+d+'</span>' : '<span style="color:#cbd5e1">—</span>'; }
            },
            {
                data: 'batch_no_label', name: 'batch_no_label', orderable: false, searchable: true, width: '110px',
                render: function(d){
                    return d !== '—'
                        ? '<span style="background:#f3e8ff;color:#6d28d9;font-size:.7rem;font-weight:700;border-radius:4px;padding:2px 7px">'+d+'</span>'
                        : '<span style="color:#cbd5e1;font-size:.78rem">—</span>';
                }
            },
            {
                data: 'expiry_date', name: 'expiry_date', className: 'text-center', orderable: true, searchable: false, width: '100px',
                render: function(d){
                    if (d === '—') return '<span style="color:#cbd5e1">—</span>';
                    var today = new Date(); var exp = new Date(d);
                    var days = Math.floor((exp - today) / 86400000);
                    var color = days < 0 ? '#dc2626' : days <= 30 ? '#be123c' : days <= 90 ? '#d97706' : '#15803d';
                    return '<span style="font-size:.78rem;font-weight:700;color:'+color+'">'+d+'</span>';
                }
            },
            {
                data: 'available_units', name: 'available_units', className: 'text-center', orderable: true, searchable: false, width: '80px',
                render: function (d) {
                    var color = d > 0 ? '#15803d' : '#9ca3af';
                    var bg = d > 0 ? '#dcfce7' : '#f3f4f6';
                    return '<span style="background:'+bg+';color:'+color+';font-size:.88rem;font-weight:800;padding:2px 10px;border-radius:20px">'+d+'</span>';
                }
            },
            { data: 'sold_units', name: 'sold_units', className: 'text-center', orderable: false, searchable: false, width: '70px',
              render: function(d){ return d > 0 ? '<span style="font-weight:700;color:#dc2626">'+d+'</span>' : '<span style="color:#9ca3af">0</span>'; } },
            {
                data: null, name: 'boxes', className: 'text-center', orderable: false, searchable: false, width: '120px',
                render: function (d) {
                    return '<span title="Sealed" style="color:#1d4ed8"><span class="box-dot" style="background:#dbeafe;border:1.5px solid #93c5fd"></span>'+d.boxes_sealed+'</span> '
                         + '<span title="Open" style="color:#d97706"><span class="box-dot" style="background:#fef3c7;border:1.5px solid #fcd34d"></span>'+d.boxes_open+'</span> '
                         + '<span title="Empty" style="color:#9ca3af"><span class="box-dot" style="background:#f3f4f6;border:1.5px solid #d1d5db"></span>'+d.boxes_empty+'</span>';
                }
            },
            { data: 'status_badge', name: 'status', className: 'text-center', orderable: false, searchable: false, width: '80px' },
        ],
        language: {
            emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-box2 fs-3 d-block mb-2"></i>No carton records found. Receive a purchase order with carton details.</div>'
        },
        order: [[0, 'desc']]
    });

    $('#applyBtn').on('click', function () { dt.ajax.reload(); });
    $('.rfilter').on('change', function () { dt.ajax.reload(); });
    $('#resetBtn').on('click', function () {
        $('#warehouseId, #categoryId, #statusFilter').val('');
        dt.ajax.reload();
    });
});

function downloadExcel() {
    var p = new URLSearchParams({
        warehouse_id: $('#warehouseId').val() || '',
        category_id:  $('#categoryId').val()  || '',
        status:       $('#statusFilter').val() || '',
    });
    window.location.href = '{{ route("report.cartonBox.excel") }}?' + p.toString();
}
</script>
@endsection
