@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Purchase Orders</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Purchase Orders</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 6px rgba(0,0,0,.05)">
            <div class="card-header d-flex align-items-center justify-content-between" style="border-bottom:1px solid #e2e8f0;padding:.85rem 1.25rem">
                <h5 class="card-title mb-0" style="font-size:.95rem;font-weight:700;color:#1e293b">
                    <i class="bi bi-receipt me-2 text-primary"></i>Purchase Order List
                </h5>
                <a href="{{ route('purchaseOrder.create') }}" class="btn btn-primary btn-sm fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> Create
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="purchaseOrderTable" class="table table-hover align-middle" style="font-size:.875rem"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    $('#purchaseOrderTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('purchaseOrder.list') }}",
            type: 'POST',
            data: function (d) { d._token = "{{ csrf_token() }}"; }
        },
        columns: [
            { title: 'Purchase No', data: 'purchase_no',  className: 'fw-semibold', orderable: true,  searchable: true  },
            { title: 'Date',        data: 'purchase_date', className: 'text-muted',  orderable: true,  searchable: false },
            { title: 'Branch',      data: 'branch',        className: 'text-muted',  orderable: false, searchable: false },
            { title: 'Supplier',    data: 'supplier',      orderable: false,          searchable: false },
            { title: 'Items',       data: 'items_count',   className: 'text-center', orderable: false, searchable: false },
            { title: 'Grand Total', data: 'grand_total',   className: 'fw-bold',     orderable: true,  searchable: false },
            { title: 'Status',      data: 'status_badge',  className: 'text-center', orderable: false, searchable: false },
            {
                title: 'Action',
                className: 'text-center',
                orderable: false,
                searchable: false,
                data: function (row) {
                    return `<div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-sm btn-warning fw-semibold px-2" onclick="editPO(${row.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                        <button class="btn btn-sm btn-primary fw-semibold px-2" onclick="receivePO(${row.id})" title="Receive Stock"><i class="bi bi-box-arrow-in-down"></i></button>
                        <button class="btn btn-sm btn-danger fw-semibold px-2"  onclick="deletePO(${row.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
                    </div>`;
                }
            }
        ]
    });
});

function editPO(id) {
    window.location.href = '{{ route("purchaseOrder.edit", ":id") }}'.replace(':id', id);
}
function receivePO(id) {
    window.location.href = '{{ route("purchaseOrder.receive", ":id") }}'.replace(':id', id);
}
function deletePO(id) {
    if(!confirm('Delete this purchase order?')) return;
    $.ajax({
        type: 'POST',
        url: "{{ route('purchaseOrder.delete') }}",
        data: { _token: "{{ csrf_token() }}", id: id },
        success: function () {
            toastr.success('Purchase order deleted.');
            $('#purchaseOrderTable').DataTable().ajax.reload(null, false);
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Unable to delete.');
        }
    });
}
</script>
@endsection
