@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Supplier Due Report</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Supplier Due</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @php $liveLabel = '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>'; @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-people"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['total']) }}</div>
                    <div class="rsl">Total Suppliers</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c5">
                <div class="rsi"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['has_due']) }}</div>
                    <div class="rsl">Has Due</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="rsv">{{ number_format($stats['no_due']) }}</div>
                    <div class="rsl">No Due</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="rsv" style="font-size:1.1rem">৳ {{ number_format($stats['total_due'],0) }}</div>
                    <div class="rsl">Total Due Amount</div>
                    {!! $liveLabel !!}
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Supplier Payables</div>
                    <div class="rpt-head-sub">Outstanding due amounts per supplier.</div>
                </div>
                <button class="btn btn-success fw-bold" onclick="downloadExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4 col-8">
                        <span class="rpt-flabel">Search Supplier</span>
                        <input type="text" id="searchName" class="form-control form-control-sm" placeholder="Name or phone...">
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Due Filter</span>
                        <select id="dueFilter" class="form-select form-select-sm rfilter">
                            <option value="">All Suppliers</option>
                            <option value="has_due">Has Due</option>
                            <option value="no_due">No Due</option>
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
                    <table id="supplierDueTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Code</th><th>Supplier Name</th><th>Phone</th><th>Email</th>
                            <th>Opening Balance</th><th>Credit Limit</th><th>Current Due</th>
                            <th>Payment Terms</th><th>Status</th>
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
    dt = $('#supplierDueTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("report.supplierDue.list") }}', type: 'POST',
            data: function (d) {
                d._token = '{{ csrf_token() }}';
                d.search_name = $('#searchName').val();
                d.due_filter  = $('#dueFilter').val();
            }
        },
        columns: [
            { data:'supplier_code', name:'supplier_code', orderable:false, searchable:true, width:'90px',
              render: function(d){ return '<span class="rpt-id">'+d+'</span>'; } },
            { data:'name', name:'name', orderable:false, searchable:true, width:'150px',
              render: function(d){ return '<span class="rpt-bold">'+d+'</span>'; } },
            { data:'phone', name:'phone', orderable:false, searchable:true, width:'110px',
              render: function(d){ return '<span style="font-size:.82rem">'+d+'</span>'; } },
            { data:'email', name:'email', orderable:false, searchable:false, width:'140px',
              render: function(d){ return '<span style="font-size:.78rem;color:#64748b">'+(d||'—')+'</span>'; } },
            { data:'opening_balance', name:'opening_balance', className:'text-end', orderable:true, searchable:false, width:'110px' },
            { data:'credit_limit',    name:'credit_limit',    className:'text-end', orderable:true, searchable:false, width:'105px' },
            { data:'due_badge', name:'current_due', className:'text-end', orderable:true, searchable:false, width:'110px' },
            { data:'payment_terms', name:'payment_terms', className:'text-center', orderable:false, searchable:false, width:'110px',
              render: function(d){ return '<span style="font-size:.78rem;color:#64748b">'+(d||'—')+'</span>'; } },
            { data:'status_badge', name:'status', className:'text-center', orderable:false, searchable:false, width:'80px' },
        ],
        language: { emptyTable:'<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No suppliers found</div>' }
    });
    $('#applyBtn').on('click', function(){ dt.ajax.reload(); });
    $('.rfilter').on('change', function(){ dt.ajax.reload(); });
    $('#searchName').on('keydown', function(e){ if(e.key==='Enter') dt.ajax.reload(); });
    $('#resetBtn').on('click', function(){
        $('#searchName').val(''); $('#dueFilter').val('');
        dt.ajax.reload();
    });
});
function downloadExcel() {
    var p = new URLSearchParams({ search_name:$('#searchName').val()||'', due_filter:$('#dueFilter').val()||'' });
    window.location.href = '{{ route("report.supplierDue.excel") }}?'+p.toString();
}
</script>
@endsection
