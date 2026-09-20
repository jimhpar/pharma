@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Product Expiry Report</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Expiry Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $liveLabel = '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>'; @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['total']) }}</div>
                    <div class="rsl">At-Risk Batches</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c5">
                <div class="rsi"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['expired']) }}</div>
                    <div class="rsl">Expired</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat" style="background:#fff1f2;border-color:#fecdd3">
                <div class="rsi" style="background:#ffe4e6;color:#be123c"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="rsv" style="color:#be123c">{{ number_format($stats['critical']) }}</div>
                    <div class="rsl" style="color:#be123c">Critical (&le;30 days)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat" style="background:#fffbeb;border-color:#fde68a">
                <div class="rsi" style="background:#fef3c7;color:#d97706"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="rsv" style="color:#d97706">{{ number_format($stats['warning']) }}</div>
                    <div class="rsl" style="color:#d97706">Warning (&le;90 days)</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Batch-Level Expiry Tracker</div>
                    <div class="rpt-head-sub">Shows all inventory batches with remaining stock and an expiry date set.</div>
                </div>
                <button class="btn btn-success fw-bold" onclick="downloadExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Expiry Status</span>
                        <select id="expiryStatus" class="form-select form-select-sm rfilter">
                            <option value="">All</option>
                            <option value="expired">Expired</option>
                            <option value="critical">Critical (&le;30 days)</option>
                            <option value="warning">Warning (&le;90 days)</option>
                            <option value="ok">OK (&gt;90 days)</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Category</span>
                        <select id="categoryId" class="form-select form-select-sm rfilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Warehouse</span>
                        <select id="warehouseId" class="form-select form-select-sm rfilter">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Expiry From</span>
                        <input type="date" id="expiryFrom" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Expiry To</span>
                        <input type="date" id="expiryTo" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1">
                        <button id="applyBtn" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill me-1"></i>Apply</button>
                        <button id="resetBtn" class="btn btn-light btn-sm" style="min-width:36px"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="rpt-scroll">
                    <table id="expiryTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Batch No</th>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>Category</th>
                            <th>Warehouse</th>
                            <th>Supplier</th>
                            <th>Available</th>
                            <th>Expiry Date</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
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
    dt = $('#expiryTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.expiry.list") }}', type: 'POST',
            data: function (d) {
                d._token        = '{{ csrf_token() }}';
                d.expiry_status = $('#expiryStatus').val();
                d.category_id   = $('#categoryId').val();
                d.warehouse_id  = $('#warehouseId').val();
                d.expiry_from   = $('#expiryFrom').val();
                d.expiry_to     = $('#expiryTo').val();
            }
        },
        columns: [
            { data: 'batch_no',       name: 'batch_no',       orderable: false, searchable: true,  width: '100px',
              render: function(d){ return '<span class="rpt-id">'+d+'</span>'; } },
            { data: 'sku_code',       name: 'sku_code',       orderable: false, searchable: true,  width: '90px',
              render: function(d){ return '<span class="rpt-sku">'+d+'</span>'; } },
            { data: 'product_name',   name: 'product_name',   orderable: false, searchable: true,  width: '160px',
              render: function(d){ return '<span class="rpt-bold">'+d+'</span>'; } },
            { data: 'variant',        name: 'variant',        orderable: false, searchable: false, width: '80px',
              render: function(d){ return d==='Single'?'<span class="text-muted small">—</span>':'<span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.68rem">'+d+'</span>'; } },
            { data: 'category',       name: 'category',       orderable: false, searchable: false, width: '100px' },
            { data: 'warehouse',      name: 'warehouse',      orderable: false, searchable: false, width: '110px',
              render: function(d){ return '<span style="font-size:.8rem;color:#64748b">'+d+'</span>'; } },
            { data: 'supplier',       name: 'supplier',       orderable: false, searchable: false, width: '110px',
              render: function(d){ return '<span style="font-size:.8rem;color:#64748b">'+d+'</span>'; } },
            { data: 'available_qty',  name: 'available_qty',  orderable: true,  searchable: false, width: '80px', className: 'text-center',
              render: function(d){ return '<span style="font-weight:800;color:#1d4ed8">'+d+'</span>'; } },
            { data: 'expiry_date',    name: 'expiry_date',    orderable: true,  searchable: false, width: '100px', className: 'text-center',
              render: function(d){ return '<span style="font-weight:700;color:#0f172a">'+d+'</span>'; } },
            { data: 'days_remaining', name: 'days_remaining', orderable: true,  searchable: false, width: '100px', className: 'text-center',
              render: function(d){
                if (d === null || d === undefined) return '<span class="text-muted">N/A</span>';
                if (d < 0)   return '<span style="font-weight:800;color:#991b1b">'+d+' days</span>';
                if (d <= 30) return '<span style="font-weight:800;color:#be123c">'+d+' days</span>';
                if (d <= 90) return '<span style="font-weight:800;color:#d97706">'+d+' days</span>';
                return '<span style="font-weight:700;color:#15803d">'+d+' days</span>';
              } },
            { data: 'status_badge',   name: 'status',         orderable: false, searchable: false, width: '90px',  className: 'text-center' },
        ],
        language: {
            emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-check-circle fs-3 d-block mb-2" style="color:#22c55e"></i>No expiring batches found for the selected filters.</div>'
        },
        order: [[8, 'asc']]
    });

    $('#applyBtn').on('click', function () { dt.ajax.reload(); });
    $('.rfilter').on('change', function () { dt.ajax.reload(); });
    $('#resetBtn').on('click', function () {
        $('#expiryStatus, #categoryId, #warehouseId').val('');
        $('#expiryFrom, #expiryTo').val('');
        dt.ajax.reload();
    });
});

function downloadExcel() {
    var p = new URLSearchParams({
        expiry_status: $('#expiryStatus').val() || '',
        category_id:   $('#categoryId').val()   || '',
        warehouse_id:  $('#warehouseId').val()  || '',
        expiry_from:   $('#expiryFrom').val()   || '',
        expiry_to:     $('#expiryTo').val()     || '',
    });
    window.location.href = '{{ route("report.expiry.excel") }}?' + p.toString();
}
</script>
@endsection
