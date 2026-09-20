@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Purchase Report</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Purchase Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $monthLabel = now()->format('F Y'); @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="rsv">{{ number_format($thisMonth['count']) }}</div>
                    <div class="rsl">Orders</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['total'],0) }}</div>
                    <div class="rsl">Total Amount</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['paid'],0) }}</div>
                    <div class="rsl">Paid</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c5">
                <div class="rsi"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['due'],0) }}</div>
                    <div class="rsl">Due</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $monthLabel }}</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">All Purchase Orders</div>
                    <div class="rpt-head-sub">Purchase history with payment status.</div>
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
                        <span class="rpt-flabel">Supplier</span>
                        <select id="supplierId" class="form-select form-select-sm rfilter">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Start Date</span>
                        <input type="date" id="startDate" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">End Date</span>
                        <input type="date" id="endDate" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Status</span>
                        <select id="statusFilter" class="form-select form-select-sm rfilter">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="ordered">Ordered</option>
                            <option value="partial">Partial</option>
                            <option value="received">Received</option>
                            <option value="canceled">Canceled</option>
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
                    <table id="purchaseTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Purchase No</th><th>Date</th><th>Branch</th><th>Supplier</th>
                            <th>Grand Total</th><th>Paid</th><th>Due</th><th>Status</th><th>Created By</th>
                        </tr></thead>
                        <tfoot><tr>
                            <th colspan="4" class="text-end" style="color:#94a3b8;font-size:.68rem">TOTALS</th>
                            <th id="ft-total"><span class="tfl">Total</span><span>0.00</span></th>
                            <th id="ft-paid"><span class="tfl">Paid</span><span>0.00</span></th>
                            <th id="ft-due"><span class="tfl">Due</span><span>0.00</span></th>
                            <th colspan="2"></th>
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
    dt = $('#purchaseTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.purchase.list") }}', type: 'POST',
            data: function (d) {
                d._token = '{{ csrf_token() }}';
                d.branch_id = $('#branchId').val(); d.supplier_id = $('#supplierId').val();
                d.startDate = $('#startDate').val(); d.endDate = $('#endDate').val();
                d.status = $('#statusFilter').val();
            },
            dataSrc: function (json) {
                var f=function(n){return Number(n||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});};
                $('#ft-total span:last').text(f(json.grandTotalSum));
                $('#ft-paid span:last').text(f(json.paidTotalSum));
                $('#ft-due span:last').text(f(json.dueTotalSum));
                return json.data;
            }
        },
        columns: [
            { data:'purchase_no', name:'purchase_no', orderable:false, searchable:true, width:'120px',
              render: function(d){ return '<span class="rpt-id">'+d+'</span>'; } },
            { data:'purchase_date', name:'purchase_date', orderable:true, searchable:false, width:'100px',
              render: function(d){ return '<span style="font-size:.82rem;font-weight:700;color:#374151">'+d+'</span>'; } },
            { data:'branch_name', name:'branch_name', orderable:false, searchable:false, width:'110px',
              render: function(d){ return '<span style="font-size:.8rem;font-weight:600;color:#475569">'+d+'</span>'; } },
            { data:'supplier_name', name:'supplier_name', orderable:false, searchable:true, width:'130px',
              render: function(d){ return '<span class="rpt-bold">'+d+'</span>'; } },
            { data:'grand_total', name:'grand_total', className:'text-end', orderable:true, searchable:false, width:'105px',
              render: function(d){ return '<span class="rpt-amt">'+d+'</span>'; } },
            { data:'paid_total',  name:'paid_total',  className:'text-end', orderable:false, searchable:false, width:'100px',
              render: function(d){ return '<span class="rpt-green">'+d+'</span>'; } },
            { data:'due_total',   name:'due_total',   className:'text-end', orderable:false, searchable:false, width:'100px',
              render: function(d,t,row){ return parseFloat(row.due_total_raw||0)>0?'<span class="rpt-red">'+d+'</span>':'<span class="rpt-green">'+d+'</span>'; } },
            { data:'status_badge', name:'status', className:'text-center', orderable:false, searchable:false, width:'95px' },
            { data:'created_by',   name:'created_by',  orderable:false, searchable:false, width:'110px',
              render: function(d){ return '<span style="font-size:.78rem;color:#64748b">'+d+'</span>'; } },
        ],
        language: { emptyTable:'<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No records found</div>' }
    });
    $('.rfilter').on('change', function(){ dt.ajax.reload(); });
    $('#applyBtn').on('click', function(){ dt.ajax.reload(); });
    $('#resetBtn').on('click', function(){
        $('#branchId,#supplierId,#statusFilter').val('');
        $('#startDate,#endDate').val('');
        dt.ajax.reload();
    });
});
function downloadExcel() {
    var p = new URLSearchParams({ branch_id:$('#branchId').val()||'', supplier_id:$('#supplierId').val()||'',
        startDate:$('#startDate').val()||'', endDate:$('#endDate').val()||'', status:$('#statusFilter').val()||'' });
    window.location.href = '{{ route("report.purchase.excel") }}?'+p.toString();
}
</script>
@endsection
