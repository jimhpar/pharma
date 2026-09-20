@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-tag-fill" style="color:#4f46e5;font-size:1.3rem"></i> Category Promotions</h2>
            <p>Manage promotional categories</p>
        </div>
        <a href="{{ route('categoryPromotion.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Promotion
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search title or category…" autocomplete="off">
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
            <table id="categoryPromotionTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#categoryPromotionTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url:"{{ route('categoryPromotion.list') }}", type:"POST", data:function(d){ d._token="{{ csrf_token() }}"; } },
        columns: [
            { title:'Serial',   data:'sort_order', name:'sort_order', className:"text-center" },
            { title:'Title',    data:'title',      name:'title',      className:"text-center" },
            { title:'Category', data:'category',   name:'category',   className:"text-center" },
            { title:'Icon',     data:'icon',       name:'icon',       className:"text-center", orderable:false, searchable:false },
            { title:'Status',   data:'status',     name:'status',     className:"text-center", searchable:false },
            { title:'Action', className:"text-center", data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editCategoryPromotion(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteCategoryPromotion(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
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
function editCategoryPromotion(x) { window.location.href = '{{ route("categoryPromotion.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteCategoryPromotion(x) {
    if (!confirm("Delete This Category Promotion?")) return false;
    $.ajax({ type:'POST', url:"{!! route('categoryPromotion.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('Category Promotion Deleted Successfully!'); $('#categoryPromotionTable').DataTable().clear().draw(); }
    });
}
</script>
@endsection
