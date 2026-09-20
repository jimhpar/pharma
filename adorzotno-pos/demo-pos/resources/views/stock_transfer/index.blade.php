@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-arrow-left-right" style="color:#4f46e5;font-size:1.3rem"></i> Stock Transfers</h2>
            <p>Warehouse and branch transfer management</p>
        </div>
        <a href="{{ route('stockTransfer.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> New Transfer
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search transfer number…" autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
            <div class="filter-divider"></div>
            <select class="filter-select no-select2" id="fSt">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="dispatched">Dispatched</option>
                <option value="received">Received</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <div class="filter-divider"></div>
            <button class="btn-reset" id="btnReset"><i class="bi bi-x-circle"></i> Reset</button>
        </div>
        <div class="active-bar d-none" id="activeBar">
            <span class="active-lbl">Active:</span>
            <div id="activeChips" style="display:flex;flex-wrap:wrap;gap:6px"></div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table id="stockTransferTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#stockTransferTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url:"{{ route('stockTransfer.list') }}", type:'POST',
            data:function(d){ d._token="{{ csrf_token() }}"; d.filter_status=$('#fSt').val(); }
        },
        columns: [
            { title:'Transfer No',  data:'transfer_no',      name:'transfer_no',      className:'text-center' },
            { title:'Requested At', data:'requested_at',     name:'requested_at',     className:'text-center' },
            { title:'From',         data:'from_location',    name:'from_location',    className:'text-center', orderable:false, searchable:false },
            { title:'To',           data:'to_location',      name:'to_location',      className:'text-center', orderable:false, searchable:false },
            { title:'Status',       data:'status_badge',     name:'status_badge',     className:'text-center', orderable:false, searchable:false },
            { title:'Requested Qty',data:'requested_total',  name:'requested_total',  className:'text-center', orderable:false, searchable:false },
            { title:'Approved Qty', data:'approved_total',   name:'approved_total',   className:'text-center', orderable:false, searchable:false },
            { title:'Dispatched Qty',data:'dispatched_total',name:'dispatched_total', className:'text-center', orderable:false, searchable:false },
            { title:'Received Qty', data:'received_total',   name:'received_total',   className:'text-center', orderable:false, searchable:false },
            { title:'Action',       data:'actions',          name:'actions',          className:'text-center', orderable:false, searchable:false }
        ]
    });

    $('#fS').on('input', function () { $('#fSClear').toggle(!!$(this).val()); clearTimeout(_dt); _dt = setTimeout(function(){ tbl.search($('#fS').val()).draw(); renderChips(); },320); });
    $('#fSClear').on('click', function () { $('#fS').val('').trigger('input'); });
    $('#fSt').on('change', function () { $(this).toggleClass('has-value',!!$(this).val()); tbl.ajax.reload(); renderChips(); });
    $('#btnReset').on('click', function () { $('#fS').val(''); $('#fSClear').hide(); $('#fSt').val('').removeClass('has-value'); tbl.search('').ajax.reload(); renderChips(); });
    function renderChips() {
        var c=[];
        if ($('#fS').val().trim()) c.push('Search: "'+$('#fS').val().trim()+'"');
        if ($('#fSt').val()) c.push('Status: '+$('#fSt option:selected').text());
        $('#activeBar').toggleClass('d-none',!c.length);
        $('#activeChips').html(c.map(function(x){ return '<span class="a-chip">'+x+'</span>'; }).join(''));
    }
});
</script>
@endsection
