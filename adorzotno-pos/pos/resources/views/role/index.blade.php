@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-shield-check" style="color:#4f46e5;font-size:1.3rem"></i> Roles</h2>
            <p>Manage user roles and permissions</p>
        </div>
        <a href="{{ route('role.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Role
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search role name or slug…" autocomplete="off">
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
            <table id="roleTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#roleTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url:"{{ route('role.list') }}", type:'POST', data:function(d){ d._token="{{ csrf_token() }}"; } },
        columns: [
            { title:'Name',         data:'name',             name:'name',             className:'text-center' },
            { title:'Slug',         data:'slug',             name:'slug',             className:'text-center' },
            { title:'Permissions',  data:'permission_count', name:'permission_count', className:'text-center', searchable:false },
            { title:'Assignments',  data:'assignment_count', name:'assignment_count', className:'text-center', searchable:false },
            { title:'Type',         data:'system_badge',     name:'system_badge',     className:'text-center', orderable:false, searchable:false },
            { title:'Action', className:'text-center', data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editRole(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteRole(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
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
function editRole(x) { window.location.href = '{{ route("role.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteRole(x) {
    if (!confirm("Delete This Role?")) return false;
    $.ajax({ type:'POST', url:"{!! route('role.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('Role deleted successfully!'); $('#roleTable').DataTable().ajax.reload(null,false); },
        error:function(xhr){ toastr.error(xhr.responseJSON?.message||'Unable to delete role.'); }
    });
}
</script>
@endsection
