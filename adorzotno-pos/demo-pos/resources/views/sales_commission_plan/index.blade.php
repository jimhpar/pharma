@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-percent" style="color:#4f46e5;font-size:1.3rem"></i> Sales Commission Plans</h2>
            <p>Manage staff commission structures</p>
        </div>
        <a href="{{ route('salesCommissionPlan.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Plan
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search plan name or code…" autocomplete="off">
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
            <table id="salesCommissionPlanTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#salesCommissionPlanTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url:"{{ route('salesCommissionPlan.list') }}", type:'POST',
            data:function(d){ d._token="{{ csrf_token() }}"; d.filter_status=$('#fSt').val(); }
        },
        columns: [
            { title:'Name',           data:'name',               name:'name',               className:'text-center' },
            { title:'Code',           data:'code',               name:'code',               className:'text-center' },
            { title:'Type',           data:'calculation_type',   name:'calculation_type',   className:'text-center' },
            { title:'Rate',           data:'rate_display',       name:'rate',               className:'text-center', searchable:false },
            { title:'Base Amount',    data:'base_amount_type',   name:'base_amount_type',   className:'text-center' },
            { title:'Scope',          data:'apply_scope',        name:'apply_scope',        className:'text-center' },
            { title:'Assigned Staff', data:'staff_count',        name:'staff_count',        className:'text-center', searchable:false },
            { title:'Status',         data:'status_badge',       name:'status_badge',       className:'text-center', orderable:false, searchable:false },
            { title:'Action', className:'text-center', data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editSalesCommissionPlan(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteSalesCommissionPlan(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
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
function editSalesCommissionPlan(x) { window.location.href = '{{ route("salesCommissionPlan.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteSalesCommissionPlan(x) {
    if (!confirm("Delete This Sales Commission Plan?")) return false;
    $.ajax({ type:'POST', url:"{!! route('salesCommissionPlan.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('Sales commission plan deleted successfully!'); $('#salesCommissionPlanTable').DataTable().ajax.reload(null,false); },
        error:function(xhr){ toastr.error(xhr.responseJSON?.message||'Unable to delete.'); }
    });
}
</script>
@endsection
