@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
.exp-cat-bar { display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem; font-size:.78rem; }
.exp-cat-track { flex:1; height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; }
.exp-cat-fill  { height:100%; border-radius:3px; background:linear-gradient(90deg,#6366f1,#8b5cf6); }
.exp-cat-name  { min-width:130px; color:#374151; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.exp-cat-amt   { min-width:90px; text-align:right; font-weight:700; color:#4f46e5; white-space:nowrap; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Expenses</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Expenses</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- This Month cards --}}
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
            {{-- Top category breakdown --}}
            <div class="rpt-stat rpt-c2 flex-column align-items-start">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#15803d;margin-bottom:.5rem">Top Categories · {{ $ml }}</div>
                @forelse($thisMonth['by_category'] as $cat => $amt)
                @php $pct = $thisMonth['total'] > 0 ? ($amt / $thisMonth['total'] * 100) : 0; @endphp
                <div class="exp-cat-bar w-100">
                    <div class="exp-cat-name">{{ $cat }}</div>
                    <div class="exp-cat-track"><div class="exp-cat-fill" style="width:{{ $pct }}%"></div></div>
                    <div class="exp-cat-amt">৳ {{ number_format($amt,0) }}</div>
                </div>
                @empty
                <div class="text-muted small">No data yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Expense List</div>
                    <div class="rpt-head-sub">Track and manage all business expenses.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('expense.report') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                        <i class="bi bi-bar-chart-line me-1"></i>Report
                    </a>
                    <a href="{{ route('expense.create') }}" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>Add Expense
                    </a>
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
                        <input type="text" id="paymentSource" class="form-control form-control-sm" placeholder="Cash, Bank, bKash…">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Start Date</span>
                        <input type="date" id="startDate" class="form-control form-control-sm rfilter">
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">End Date</span>
                        <input type="date" id="endDate" class="form-control form-control-sm rfilter">
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
                    <table id="expenseTable" class="table align-middle mb-0 rpt-table" style="min-width:800px"></table>
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
    dt = $('#expenseTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("expense.list") }}', type: 'POST',
            data: function (d) {
                d._token         = '{{ csrf_token() }}';
                d.branch_id      = $('#branchId').val();
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
              render:function(d){ return '<span style="font-size:.82rem;font-weight:700;color:#374151">'+d+'</span>'; } },
            { title:'Branch', data:'branch_name', name:'branch_name', orderable:false, searchable:false, width:'100px',
              render:function(d){ return '<span style="font-size:.8rem;color:#475569">'+d+'</span>'; } },
            { title:'Category', data:'category_name', name:'category_name', orderable:false, searchable:false, width:'120px',
              render:function(d){ return '<span style="background:#e0e7ff;color:#3730a3;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:5px">'+d+'</span>'; } },
            { title:'Payment', data:'payment_source', name:'payment_source', orderable:true, searchable:true, width:'100px',
              render:function(d){ return d?'<span style="font-size:.8rem;color:#64748b">'+d+'</span>':'<span class="text-muted">—</span>'; } },
            { title:'Vendor', data:'vendor_name', name:'vendor_name', orderable:true, searchable:true, width:'120px',
              render:function(d){ return d?'<span style="font-size:.82rem;font-weight:600;color:#1e293b">'+d+'</span>':'<span class="text-muted small">—</span>'; } },
            { title:'Amount', data:'amount', name:'amount', className:'text-end', orderable:true, searchable:false, width:'100px',
              render:function(d){ return '<span style="font-weight:800;color:#dc2626">'+d+'</span>'; } },
            { title:'', data:'attachment', name:'attachment', className:'text-center', orderable:false, searchable:false, width:'40px' },
            { title:'', data:null, className:'text-center', orderable:false, searchable:false, width:'80px',
              render:function(data){ return '<a class="btn btn-sm btn-outline-warning px-2 py-1 me-1" onclick="editExp('+data.id+')" title="Edit"><i class="bi bi-pencil"></i></a>'
                + '<button class="btn btn-sm btn-outline-danger px-2 py-1" onclick="delExp('+data.id+')" title="Delete"><i class="bi bi-trash"></i></button>'; } }
        ],
        language: { emptyTable:'<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No expenses found</div>' }
    });

    $('#applyBtn').on('click', function(){ dt.ajax.reload(); });
    $('.rfilter').on('change', function(){ dt.ajax.reload(); });
    $('#paymentSource').on('keydown', function(e){ if(e.key==='Enter') dt.ajax.reload(); });
    $('#resetBtn').on('click', function(){
        $('#branchId,#categoryId').val('');
        $('#startDate,#endDate,#paymentSource').val('');
        dt.ajax.reload();
    });
});

function editExp(id) {
    window.location.href = '{{ route("expense.edit",":id") }}'.replace(':id', id);
}

function delExp(id) {
    if (!confirm('Delete this expense?')) return;
    $.post('{{ route("expense.delete") }}', { _token:'{{ csrf_token() }}', id:id }, function() {
        toastr.success('Expense deleted.');
        dt.ajax.reload(null, false);
    }).fail(function(xhr){ toastr.error(xhr.responseJSON?.message||'Unable to delete.'); });
}
</script>
@endsection
