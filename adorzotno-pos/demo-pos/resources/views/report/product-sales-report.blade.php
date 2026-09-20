@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Product Sales Report</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Product Sales</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- This Month stat cards --}}
    @php $monthLabel = now()->format('F Y'); @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-cart-check"></i></div>
                <div>
                    <div class="rsv">{{ number_format($thisMonth['qty']) }}</div>
                    <div class="rsl">Units Sold</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="rsv">৳ {{ number_format($thisMonth['revenue'],0) }}</div>
                    <div class="rsl">Revenue</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-box-arrow-in-down"></i></div>
                <div>
                    <div class="rsv">৳ {{ number_format($thisMonth['cost'],0) }}</div>
                    <div class="rsl">Total Cost</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c4">
                <div class="rsi"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="rsv">৳ {{ number_format($thisMonth['profit'],0) }}</div>
                    <div class="rsl">Profit</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Product-wise Sales</div>
                    <div class="rpt-head-sub">Delivered & completed orders — line item breakdown.</div>
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
                        <span class="rpt-flabel">Start Date</span>
                        <input type="date" id="startDate" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">End Date</span>
                        <input type="date" id="endDate" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Category</span>
                        <select id="categoryId" class="form-select form-select-sm rfilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Brand</span>
                        <select id="brandId" class="form-select form-select-sm rfilter">
                            <option value="">All Brands</option>
                            @foreach($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Channel</span>
                        <select id="salesChannel" class="form-select form-select-sm rfilter">
                            <option value="">All Channels</option>
                            @foreach($salesChannels as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
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
                    <table id="productSalesTable" class="table align-middle mb-0 rpt-table" style="min-width:1050px">
                        <thead><tr>
                            <th>Product</th><th>SKU</th><th>Variant</th><th>Category</th>
                            <th>Branch</th><th>Channel</th><th>Qty</th>
                            <th>Unit Price</th><th>Discount</th><th>Total</th><th>Cost</th><th>Profit</th>
                        </tr></thead>
                        <tfoot><tr>
                            <th colspan="6" class="text-end" style="color:#94a3b8;font-size:.68rem">TOTALS</th>
                            <th id="ft-qty"><span class="tfl">Qty</span><span>0</span></th>
                            <th></th><th></th>
                            <th id="ft-total"><span class="tfl">Revenue</span><span>0.00</span></th>
                            <th id="ft-cost"><span class="tfl">Cost</span><span>0.00</span></th>
                            <th id="ft-profit"><span class="tfl">Profit</span><span>0.00</span></th>
                        </tr></tfoot>
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
    dt = $('#productSalesTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.productSales.list") }}', type: 'POST',
            data: function (d) {
                d._token = '{{ csrf_token() }}';
                d.startDate = $('#startDate').val(); d.endDate = $('#endDate').val();
                d.category_id = $('#categoryId').val(); d.brand_id = $('#brandId').val();
                d.salesChannel = $('#salesChannel').val(); d.branch_id = $('#branchId').val();
            },
            dataSrc: function (json) {
                var f = function(n){ return Number(n||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2}); };
                $('#ft-qty span:last').text(Number(json.totalQty||0).toLocaleString('en'));
                $('#ft-total span:last').text(f(json.totalRevenue));
                $('#ft-cost span:last').text(f(json.totalCost));
                $('#ft-profit span:last').text(f(json.totalProfit));
                return json.data;
            }
        },
        columns: [
            { data:'product_name', name:'product_name', orderable:false, searchable:true, width:'160px',
              render: function(d){ return '<span class="rpt-bold">'+d+'</span>'; } },
            { data:'sku_code', name:'sku_code', orderable:false, searchable:true, width:'100px',
              render: function(d){ return '<span class="rpt-sku">'+d+'</span>'; } },
            { data:'variant', name:'variant', orderable:false, searchable:false, width:'90px',
              render: function(d){ return d==='Single'?'<span class="text-muted small">—</span>':'<span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.68rem">'+d+'</span>'; } },
            { data:'category', name:'category', orderable:false, searchable:false, width:'100px' },
            { data:'branch', name:'branch', orderable:false, searchable:false, width:'100px',
              render: function(d){ return '<span style="font-size:.8rem;font-weight:600;color:#475569">'+d+'</span>'; } },
            { data:'sales_channel', name:'sales_channel', className:'text-center', orderable:false, searchable:false, width:'80px',
              render: function(d){
                var m={'pos':['#ede9fe','#5b21b6','POS'],'online':['#fff7ed','#9a3412','Online']};
                var c=m[d]||['#f1f5f9','#64748b',d||'N/A'];
                return '<span style="background:'+c[0]+';color:'+c[1]+';font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:5px">'+c[2]+'</span>';
              } },
            { data:'qty', name:'qty', className:'text-center', orderable:false, searchable:false, width:'60px',
              render: function(d){ return '<span style="font-weight:800;color:#0f172a">'+d+'</span>'; } },
            { data:'unit_price', name:'unit_price', className:'text-end', orderable:false, searchable:false, width:'85px' },
            { data:'discount',   name:'discount',   className:'text-end', orderable:false, searchable:false, width:'80px',
              render: function(d){ return '<span class="rpt-red">'+d+'</span>'; } },
            { data:'total',  name:'total',  className:'text-end', orderable:false, searchable:false, width:'90px',
              render: function(d){ return '<span class="rpt-amt">'+d+'</span>'; } },
            { data:'cost',   name:'cost',   className:'text-end', orderable:false, searchable:false, width:'85px',
              render: function(d){ return '<span style="font-weight:700;color:#64748b">'+d+'</span>'; } },
            { data:'profit', name:'profit', className:'text-end', orderable:false, searchable:false, width:'90px',
              render: function(d){ return '<span class="rpt-green">'+d+'</span>'; } },
        ],
        language: { emptyTable:'<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No records found</div>' }
    });
    $('.rfilter').on('change', function(){ dt.ajax.reload(); });
    $('#applyBtn').on('click', function(){ dt.ajax.reload(); });
    $('#resetBtn').on('click', function(){
        $('#branchId,#categoryId,#brandId,#salesChannel').val('');
        $('#startDate,#endDate').val('');
        dt.ajax.reload();
    });
});
function downloadExcel() {
    var p = new URLSearchParams({ startDate:$('#startDate').val()||'', endDate:$('#endDate').val()||'',
        category_id:$('#categoryId').val()||'', brand_id:$('#brandId').val()||'',
        salesChannel:$('#salesChannel').val()||'', branch_id:$('#branchId').val()||'' });
    window.location.href = '{{ route("report.productSales.excel") }}?'+p.toString();
}
</script>
@endsection
