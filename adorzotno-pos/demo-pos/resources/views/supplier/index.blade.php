@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-people" style="color:#4f46e5;font-size:1.3rem"></i> Suppliers</h2>
            <p>Manage your supplier list</p>
        </div>
        <a href="{{ route('supplier.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Supplier
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search" id="fSearchWrap">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search name, email, phone, code…" autocomplete="off">
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
            <table id="supplierTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#supplierTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('supplier.list') }}",
            type: "POST",
            data: function (d) {
                d._token = "{{ csrf_token() }}";
                d.filter_status = $('#fSt').val();
            },
        },
        columns: [
            { title: 'Code',            data: 'supplier_code',        name: 'supplier_code',    className: "text-center" },
            { title: 'Name',            data: 'name',                 name: 'name',             className: "text-center" },
            { title: 'Email',           data: 'email',                name: 'email',            className: "text-center" },
            { title: 'Phone',           data: 'phone',                name: 'phone',            className: "text-center" },
            { title: 'Payment Terms',   data: 'payment_terms',        name: 'payment_terms',    className: "text-center" },
            { title: 'Opening Balance', data: 'opening_balance',      name: 'opening_balance',  className: "text-center" },
            { title: 'Credit Limit',    data: 'credit_limit_display', name: 'credit_limit',     className: "text-center", orderable: false, searchable: false },
            { title: 'Outstanding Due', data: 'current_due_display',  name: 'current_due',      className: "text-center", orderable: false, searchable: false },
            { title: 'Aging',           data: 'aging_summary',        name: 'aging_summary',    className: "text-center", orderable: false, searchable: false },
            { title: 'Status',          data: 'status_badge',         name: 'status',           className: "text-center", orderable: false, searchable: false },
            {
                title: 'Action', className: "text-center",
                data: function (data) {
                    return '<a title="statement" class="btn btn-info btn-xs me-2" data-panel-id="' + data.id + '" onclick="viewStatement(this)"><i class="fa fa-file-text"></i></a>' +
                        '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="' + data.id + '" onclick="editSupplier(this)"><i class="fa fa-edit"></i></a>' +
                        '<a title="delete" class="btn btn-danger btn-xs" data-panel-id="' + data.id + '" onclick="deleteSupplier(this)"><i class="fa fa-trash"></i></a>';
                },
                orderable: false, searchable: false
            }
        ]
    });

    $('#fS').on('input', function () {
        $('#fSClear').toggle(!!$(this).val());
        clearTimeout(_dt);
        _dt = setTimeout(function () { tbl.search($('#fS').val()).draw(); renderChips(); }, 320);
    });
    $('#fSClear').on('click', function () { $('#fS').val('').trigger('input'); });

    $('#fSt').on('change', function () {
        $(this).toggleClass('has-value', !!$(this).val());
        tbl.ajax.reload(); renderChips();
    });

    $('#btnReset').on('click', function () {
        $('#fS').val(''); $('#fSClear').hide();
        $('#fSt').val('').removeClass('has-value');
        tbl.search('').ajax.reload(); renderChips();
    });

    function renderChips() {
        var c = [];
        if ($('#fS').val().trim()) c.push({ label:'Search: "' + $('#fS').val().trim() + '"' });
        if ($('#fSt').val()) c.push({ label:'Status: ' + $('#fSt option:selected').text() });
        $('#activeBar').toggleClass('d-none', !c.length);
        $('#activeChips').html(c.map(function(x){ return '<span class="a-chip">'+x.label+'</span>'; }).join(''));
    }
});

function editSupplier(x) {
    window.location.href = '{{ route("supplier.edit", ":id") }}'.replace(':id', $(x).data('panel-id'));
}
function viewStatement(x) {
    window.location.href = '{{ route("supplier.statement", ":id") }}'.replace(':id', $(x).data('panel-id'));
}
function deleteSupplier(x) {
    if (!confirm("Delete This Supplier?")) return false;
    $.ajax({ type:'POST', url:"{!! route('supplier.delete') !!}",
        data:{ _token:"{{ csrf_token() }}", id:$(x).data('panel-id') },
        success:function(){ toastr.success('Supplier Deleted Successfully!'); $('#supplierTable').DataTable().ajax.reload(null,false); }
    });
}
</script>
@endsection
