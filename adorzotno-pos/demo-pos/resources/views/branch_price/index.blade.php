@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Branch Prices</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Branch Prices</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">Branch Price List</h5>
                <a href="{{ route('branchPrice.create') }}" class="text-white"><button class="btn btn-primary fw-bold"><i class="bi bi-plus pe-1 fs-5"></i>Create</button></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="branchPriceTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
    $(document).ready(function () {
        $('#branchPriceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('branchPrice.list') }}",
                type: 'POST',
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                },
            },
            columns: [
                { title: 'Branch', data: 'branch_name', name: 'branch.name', className: 'text-center' },
                { title: 'Product', data: 'product_name', name: 'sku.product.name', className: 'text-center' },
                { title: 'SKU', data: 'sku_code', name: 'sku.sku_code', className: 'text-center' },
                { title: 'Retail', data: 'retail_price', name: 'retail_price', className: 'text-center' },
                { title: 'Wholesale', data: 'wholesale_price', name: 'wholesale_price', className: 'text-center' },
                { title: 'Minimum', data: 'minimum_selling_price', name: 'minimum_selling_price', className: 'text-center' },
                { title: 'Online', data: 'online_price', name: 'online_price', className: 'text-center' },
                { title: 'Status', data: 'status_badge', name: 'status_badge', className: 'text-center', orderable: false, searchable: false },
                {
                    title: 'Action',
                    className: 'text-center',
                    data: function (data) {
                        return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="' + data.id + '" onclick="editBranchPrice(this)"><i class="fa fa-edit"></i></a>' +
                            '<a title="delete" class="btn btn-danger btn-xs" data-panel-id="' + data.id + '" onclick="deleteBranchPrice(this)"><i class="fa fa-trash"></i></a>';
                    },
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });

    function editBranchPrice(x) {
        let id = $(x).data('panel-id');
        let url = '{{ route("branchPrice.edit", ":id") }}';
        window.location.href = url.replace(':id', id);
    }

    function deleteBranchPrice(x) {
        let id = $(x).data('panel-id');
        if (!confirm("Delete This Branch Price?")) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: "{!! route('branchPrice.delete') !!}",
            cache: false,
            data: { _token: "{{ csrf_token() }}", id: id },
            success: function () {
                toastr.success('Branch price deleted successfully!');
                $('#branchPriceTable').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Unable to delete branch price.');
            }
        });
    }
</script>
@endsection
