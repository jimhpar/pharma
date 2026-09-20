@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-clipboard2-data" style="color:#4f46e5;font-size:1.3rem"></i> Inventory Documents</h2>
            <p>Opening stock, issues, and adjustments</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="{{ route('inventoryAdjustment.openingStock.create') }}" class="btn-new" style="background:#0891b2">Opening Stock</a>
            <a href="{{ route('inventoryAdjustment.stockIssue.create') }}" class="btn-new" style="background:#d97706">Stock Issue</a>
            <a href="{{ route('inventoryAdjustment.create') }}" class="btn-new">
                <i class="bi bi-plus-lg" style="font-size:15px"></i> Adjustment
            </a>
        </div>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search adjustment number or date…" autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
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
            <table id="inventoryAdjustmentTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#inventoryAdjustmentTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url:"{{ route('inventoryAdjustment.list') }}", type:'POST', data:function(d){ d._token="{{ csrf_token() }}"; } },
        columns: [
            { title:'Adjustment No', data:'adjustment_no',  name:'adjustment_no',  className:'text-center' },
            { title:'Type',          data:'document_type',  name:'document_type',  className:'text-center', orderable:false, searchable:false },
            { title:'Date',          data:'adjustment_date',name:'adjustment_date', className:'text-center' },
            { title:'Branch',        data:'branch',         name:'branch',         className:'text-center', orderable:false, searchable:false },
            { title:'Warehouse',     data:'warehouse',      name:'warehouse',      className:'text-center', orderable:false, searchable:false },
            { title:'Items',         data:'item_count',     name:'item_count',     className:'text-center', orderable:false, searchable:false },
            { title:'Total Qty',     data:'total_quantity', name:'total_quantity', className:'text-center', orderable:false, searchable:false },
            { title:'Note',          data:'note',           name:'note',           className:'text-center', orderable:false, searchable:false }
        ]
    });

    $('#fS').on('input', function () { $('#fSClear').toggle(!!$(this).val()); clearTimeout(_dt); _dt = setTimeout(function(){ tbl.search($('#fS').val()).draw(); renderChips(); },320); });
    $('#fSClear').on('click', function () { $('#fS').val('').trigger('input'); });
    $('#btnReset').on('click', function () { $('#fS').val(''); $('#fSClear').hide(); tbl.search('').draw(); renderChips(); });
    function renderChips() {
        var c=[];
        if ($('#fS').val().trim()) c.push('Search: "'+$('#fS').val().trim()+'"');
        $('#activeBar').toggleClass('d-none',!c.length);
        $('#activeChips').html(c.map(function(x){ return '<span class="a-chip">'+x+'</span>'; }).join(''));
    }
});
</script>
@endsection
