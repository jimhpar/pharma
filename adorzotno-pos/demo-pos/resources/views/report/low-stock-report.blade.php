@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Low Stock Alert</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Low Stock Alert</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $liveLabel = '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>'; @endphp
    <div class="row g-3 mb-3">
        <div class="col-4">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['total']) }}</div>
                    <div class="rsl">Total Alerts</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="rpt-stat rpt-c5">
                <div class="rsi"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['out_of_stock']) }}</div>
                    <div class="rsl">Out of Stock</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="rpt-stat" style="background:#fffbeb;border-color:#fde68a">
                <div class="rsi" style="background:#fef3c7;color:#d97706"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <div class="rsv" style="color:#d97706">{{ number_format($stats['low_stock']) }}</div>
                    <div class="rsl" style="color:#d97706">Low Stock</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Products Below Reorder Level</div>
                    <div class="rpt-head-sub">Items that need immediate restocking.</div>
                </div>
                <button class="btn btn-success fw-bold" onclick="downloadExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    @if(!empty($branches) && $branches->isNotEmpty())
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Branch</span>
                        <select id="branchId" class="form-select form-select-sm rfilter">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Warehouse</span>
                        <select id="warehouseId" class="form-select form-select-sm rfilter">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Stock Status</span>
                        <select id="stockFilter" class="form-select form-select-sm rfilter">
                            <option value="">All (Low + Out)</option>
                            <option value="out_of_stock">Out of Stock Only</option>
                            <option value="low_stock">Low Stock Only</option>
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
                    <table id="lowStockTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>SKU</th><th>Product</th><th>Variant</th><th>Category</th>
                            <th>Branch</th><th>Warehouse</th>
                            <th>Available</th><th>Reorder Level</th><th>Shortage</th><th>Status</th>
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
    dt = $('#lowStockTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.lowStock.list") }}', type: 'POST',
            data: function (d) {
                d._token      = '{{ csrf_token() }}';
                d.branch_id   = $('#branchId').val();
                d.warehouse_id= $('#warehouseId').val();
                d.stock_filter= $('#stockFilter').val();
            }
        },
        columns: [
            { data:'sku_code', name:'sku_code', orderable:false, searchable:true, width:'100px',
              render: function(d){ return '<span class="rpt-sku">'+d+'</span>'; } },
            { data:'product_name', name:'product_name', orderable:false, searchable:true, width:'160px',
              render: function(d){ return '<span class="rpt-bold">'+d+'</span>'; } },
            { data:'variant', name:'variant', orderable:false, searchable:false, width:'90px',
              render: function(d){ return d==='Single'?'<span class="text-muted small">—</span>':'<span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.68rem">'+d+'</span>'; } },
            { data:'category', name:'category', orderable:false, searchable:false, width:'100px' },
            { data:'branch',    name:'branch',    orderable:false, searchable:false, width:'100px',
              render: function(d){ return '<span style="font-size:.8rem;font-weight:600;color:#475569">'+d+'</span>'; } },
            { data:'warehouse', name:'warehouse', orderable:false, searchable:false, width:'120px',
              render: function(d){ return '<span style="font-size:.8rem;color:#64748b">'+d+'</span>'; } },
            { data:'available', name:'available', className:'text-center', orderable:true, searchable:false, width:'80px',
              render: function(d){
                var cls = d<=0 ? 'background:#fee2e2;color:#991b1b' : 'background:#fef3c7;color:#92400e';
                return '<span style="'+cls+';font-weight:800;padding:3px 10px;border-radius:7px;display:inline-block">'+d+'</span>';
              } },
            { data:'reorder', name:'reorder', className:'text-center', orderable:true, searchable:false, width:'90px',
              render: function(d){ return '<span style="font-weight:700;color:#64748b"><i class="bi bi-bell me-1" style="font-size:.65rem"></i>'+d+'</span>'; } },
            { data:null, name:'shortage', className:'text-center', orderable:false, searchable:false, width:'80px',
              render: function(data){
                var shortage = Math.max(0, data.reorder - data.available);
                return shortage>0?'<span style="font-weight:800;color:#dc2626">'+shortage+'</span>':'<span class="text-muted">0</span>';
              } },
            { data:'status_badge', name:'status', className:'text-center', orderable:false, searchable:false, width:'100px' },
        ],
        language: {
            emptyTable:'<div class="text-center py-4 text-muted"><i class="bi bi-check-circle fs-3 d-block mb-2" style="color:#22c55e"></i>All products are well-stocked!</div>'
        },
        order: [[6, 'asc']]
    });
    $('#applyBtn').on('click', function(){ dt.ajax.reload(); });
    $('.rfilter').on('change', function(){ dt.ajax.reload(); });
    $('#resetBtn').on('click', function(){
        $('#branchId,#warehouseId,#stockFilter').val('');
        dt.ajax.reload();
    });
});
function downloadExcel() {
    var p = new URLSearchParams({ branch_id:$('#branchId').val()||'', warehouse_id:$('#warehouseId').val()||'', stock_filter:$('#stockFilter').val()||'' });
    window.location.href = '{{ route("report.lowStock.excel") }}?'+p.toString();
}
</script>
@endsection
