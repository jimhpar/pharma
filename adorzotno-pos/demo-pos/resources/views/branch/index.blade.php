@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-shop" style="color:#4f46e5;font-size:1.3rem"></i> Branches</h2>
            <p>Manage store branches</p>
        </div>
        <a href="{{ route('branch.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Branch
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search name, code or prefix…" autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
            <div class="filter-divider"></div>
            <select class="filter-select no-select2" id="fSt">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
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
            <table id="branchTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#branchTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: "{{ route('branch.list') }}", type: 'POST',
            data: function (d) { d._token = "{{ csrf_token() }}"; d.filter_status = $('#fSt').val(); },
        },
        columns: [
            { title:'Name',              data:'name',              name:'name',              className:'text-center' },
            { title:'Code',              data:'code',              name:'code',              className:'text-center' },
            { title:'Default Warehouse', data:'default_warehouse', name:'default_warehouse', className:'text-center', orderable:false, searchable:false },
            { title:'Invoice Prefix',    data:'invoice_prefix',    name:'invoice_prefix',    className:'text-center' },
            { title:'Phone',             data:'phone',             name:'phone',             className:'text-center', orderable:false, searchable:false },
            { title:'Email',             data:'email',             name:'email',             className:'text-center', orderable:false, searchable:false },
            { title:'Status',            data:'status',            name:'status',            className:'text-center', orderable:false, searchable:false },
            { title:'Action', className:'text-center', data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editBranch(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteBranch(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
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
function editBranch(x) { window.location.href = '{{ route("branch.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteBranch(x) {
    if (!confirm("Delete This Branch?")) return false;
    $.ajax({ type:'POST', url:"{!! route('branch.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('Branch Deleted Successfully!'); $('#branchTable').DataTable().ajax.reload(null,false); }
    });
}
</script>
@endsection
