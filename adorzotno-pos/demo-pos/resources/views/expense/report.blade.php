@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Expense Report</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('expense.show') }}">Expenses</a></li>
                        <li class="breadcrumb-item active">Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $ml = $thisMonth['month_label']; @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c5">
                <div class="rsi"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['total'],0) }}</div>
                    <div class="rsl">Total Spent</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $ml }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-receipt-cutoff"></i></div>
                <div>
                    <div class="rsv">{{ number_format($thisMonth['count']) }}</div>
                    <div class="rsl">Transactions</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $ml }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c4">
                <div class="rsi"><i class="bi bi-calculator"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.1rem">৳ {{ number_format($thisMonth['avg'],0) }}</div>
                    <div class="rsl">Avg / Entry</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $ml }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-pie-chart"></i></div>
                <div>
                    <div class="rsv">{{ $thisMonth['by_category']->count() }}</div>
                    <div class="rsl">Categories Used</div>
                    <div class="rsb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $ml }}</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Expense Report</div>
                    <div class="rpt-head-sub">Filtered expense data with export options.</div>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="downloadExpenseExcel()" class="btn btn-success btn-sm fw-semibold">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button onclick="downloadExpensePdf()" class="btn btn-danger btn-sm fw-semibold">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    @if(!empty($branches) && count($branches))
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Branch</span>
                        <select id="branchId" class="form-select form-select-sm rfilter">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Month</span>
                        <input type="month" id="month" class="form-control form-control-sm rfilter" value="{{ now()->format('Y-m') }}">
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
                        <span class="rpt-flabel">Category</span>
                        <select id="categoryId" class="form-select form-select-sm rfilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->parent ? $c->parent->name.' / ' : '' }}{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Payment Source</span>
                        <input type="text" id="paymentSource" class="form-control form-control-sm" placeholder="Cash, Bank…">
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1">
                        <button id="applyBtn" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill me-1"></i>Apply</button>
                        <button id="resetBtn" class="btn btn-light btn-sm" style="min-width:36px"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            </div>

            <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#fffbeb;border-bottom:1px solid #fde68a">
                <span style="font-size:.78rem;font-weight:600;color:#92400e">Filtered Total:</span>
                <span id="expenseTotal" style="font-size:.9rem;font-weight:800;color:#dc2626">৳ 0.00</span>
            </div>

            <div class="card-body p-0">
                <div class="rpt-scroll">
                    <table id="expenseReportTable" class="table align-middle mb-0 rpt-table" style="min-width:780px"></table>
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
    dt = $('#expenseReportTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("expense.list") }}', type: 'POST',
            data: function (d) {
                d._token         = '{{ csrf_token() }}';
                d.branch_id      = $('#branchId').val();
                d.month          = $('#month').val();
                d.startDate      = $('#startDate').val();
                d.endDate        = $('#endDate').val();
                d.category_id    = $('#categoryId').val();
                d.payment_source = $('#paymentSource').val();
            },
            dataSrc: function (json) {
                var t = Number(json.totalAmount||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
                $('#expenseTotal').text('৳ ' + t);
                return json.data;
            }
        },
        columns: [
            { title:'Date', data:'expense_date', name:'expense_date', orderable:true, searchable:true, width:'95px',
              render:function(d){ return '<span style="font-size:.82rem;font-weight:700">'+d+'</span>'; } },
            { title:'Branch', data:'branch_name', name:'branch_name', orderable:false, searchable:false, width:'100px' },
            { title:'Category', data:'category_name', name:'category_name', orderable:false, searchable:false, width:'120px',
              render:function(d){ return '<span style="background:#e0e7ff;color:#3730a3;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:5px">'+d+'</span>'; } },
            { title:'Payment', data:'payment_source', name:'payment_source', orderable:true, searchable:true, width:'105px',
              render:function(d){ return d||'<span class="text-muted">—</span>'; } },
            { title:'Vendor', data:'vendor_name', name:'vendor_name', orderable:true, searchable:true, width:'120px',
              render:function(d){ return d?'<span style="font-weight:600">'+d+'</span>':'<span class="text-muted small">—</span>'; } },
            { title:'Amount', data:'amount', name:'amount', className:'text-end', orderable:true, searchable:false, width:'105px',
              render:function(d){ return '<span style="font-weight:800;color:#dc2626">'+d+'</span>'; } },
            { title:'Note', data:'note', name:'note', orderable:false, searchable:true, width:'180px',
              render:function(d){ return d?'<span style="font-size:.78rem;color:#64748b">'+d+'</span>':'<span class="text-muted small">—</span>'; } },
        ],
        language: { emptyTable:'<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No expenses found</div>' }
    });

    $('#applyBtn').on('click', function(){ dt.ajax.reload(); });
    $('.rfilter').on('change', function(){ dt.ajax.reload(); });
    $('#paymentSource').on('keydown', function(e){ if(e.key==='Enter') dt.ajax.reload(); });
    $('#resetBtn').on('click', function(){
        $('#branchId,#categoryId').val('');
        $('#month').val('{{ now()->format("Y-m") }}');
        $('#startDate,#endDate,#paymentSource').val('');
        dt.ajax.reload();
    });
});

function downloadExpensePdf() {
    var p = new URLSearchParams({ branch_id:$('#branchId').val()||'', month:$('#month').val()||'',
        startDate:$('#startDate').val()||'', endDate:$('#endDate').val()||'',
        category_id:$('#categoryId').val()||'', payment_source:$('#paymentSource').val()||'' });
    window.location.href = '{{ route("expense.report.pdf") }}?' + p.toString();
}
function downloadExpenseExcel() {
    var p = new URLSearchParams({ branch_id:$('#branchId').val()||'', month:$('#month').val()||'',
        startDate:$('#startDate').val()||'', endDate:$('#endDate').val()||'',
        category_id:$('#categoryId').val()||'', payment_source:$('#paymentSource').val()||'' });
    window.location.href = '{{ route("expense.report.excel") }}?' + p.toString();
}
</script>
@endsection
