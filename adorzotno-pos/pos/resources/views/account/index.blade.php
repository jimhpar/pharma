@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-journal-bookmark" style="color:#4f46e5;font-size:1.3rem"></i> Chart of Accounts</h2>
            <p>Manage your accounting structure</p>
        </div>
        <a href="{{ route('account.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Account
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search account name or code…" autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
            <div class="filter-divider"></div>
            <select class="filter-select no-select2" id="fT">
                <option value="">All Types</option>
                <option value="asset">Asset</option>
                <option value="liability">Liability</option>
                <option value="equity">Equity</option>
                <option value="revenue">Revenue</option>
                <option value="expense">Expense</option>
                <option value="bank">Bank</option>
                <option value="cash">Cash</option>
            </select>
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
            <table id="accountTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#accountTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: "{{ route('account.list') }}", type: "POST",
            data: function (d) { d._token="{{ csrf_token() }}"; d.filter_status=$('#fSt').val(); d.filter_type=$('#fT').val(); },
        },
        columns: [
            { title:'Code',           data:'account_code',            name:'account_code',           className:"text-center" },
            { title:'Name',           data:'account_name',            name:'account_name',           className:"text-center" },
            { title:'Type',           data:'type_label',              name:'type_label',             className:"text-center", orderable:false },
            { title:'Opening',        data:'opening_balance_display', name:'opening_balance_display', className:"text-center", orderable:false },
            { title:'Ledger Balance', data:'ledger_balance',          name:'ledger_balance',         className:"text-center", orderable:false },
            { title:'Status',         data:'status_badge',            name:'status_badge',           className:"text-center", orderable:false, searchable:false },
            { title:'Action', className:"text-center", data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editAccount(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteAccount(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
        ]
    });

    $('#fS').on('input', function () { $('#fSClear').toggle(!!$(this).val()); clearTimeout(_dt); _dt = setTimeout(function(){ tbl.search($('#fS').val()).draw(); renderChips(); },320); });
    $('#fSClear').on('click', function () { $('#fS').val('').trigger('input'); });
    $('#fSt, #fT').on('change', function () { $(this).toggleClass('has-value',!!$(this).val()); tbl.ajax.reload(); renderChips(); });
    $('#btnReset').on('click', function () { $('#fS').val(''); $('#fSClear').hide(); $('#fSt,#fT').val('').removeClass('has-value'); tbl.search('').ajax.reload(); renderChips(); });
    function renderChips() {
        var c=[];
        if ($('#fS').val().trim()) c.push('Search: "'+$('#fS').val().trim()+'"');
        if ($('#fT').val()) c.push('Type: '+$('#fT option:selected').text());
        if ($('#fSt').val()) c.push('Status: '+$('#fSt option:selected').text());
        $('#activeBar').toggleClass('d-none',!c.length);
        $('#activeChips').html(c.map(function(x){ return '<span class="a-chip">'+x+'</span>'; }).join(''));
    }
});
function editAccount(x) { window.location.href = '{{ route("account.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteAccount(x) {
    if (!confirm("Delete This Account?")) return false;
    $.ajax({ type:'POST', url:"{!! route('account.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('Account Deleted Successfully!'); $('#accountTable').DataTable().ajax.reload(null,false); },
        error:function(xhr){ toastr.error(xhr.responseJSON?.message||'Unable to delete account.'); }
    });
}
</script>
@endsection
