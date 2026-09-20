@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Permissions</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Permissions</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">Permission List</h5>
                <a href="{{ route('permission.create') }}" class="text-white"><button class="btn btn-primary fw-bold"><i class="bi bi-plus pe-1 fs-5"></i>Create</button></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="permissionTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
    $(document).ready(function () {
        $('#permissionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('permission.list') }}",
                type: 'POST',
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                },
            },
            columns: [
                { title: 'Module', data: 'module', name: 'module', className: 'text-center' },
                { title: 'Action', data: 'action', name: 'action', className: 'text-center' },
                { title: 'Slug', data: 'slug', name: 'slug', className: 'text-center' },
                { title: 'Roles', data: 'role_count', name: 'role_count', className: 'text-center', searchable: false },
                {
                    title: 'Action',
                    className: 'text-center',
                    data: function (data) {
                        return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="' + data.id + '" onclick="editPermission(this)"><i class="fa fa-edit"></i></a>' +
                            '<a title="delete" class="btn btn-danger btn-xs" data-panel-id="' + data.id + '" onclick="deletePermission(this)"><i class="fa fa-trash"></i></a>';
                    },
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });

    function editPermission(x) {
        let id = $(x).data('panel-id');
        let url = '{{ route("permission.edit", ":id") }}';
        window.location.href = url.replace(':id', id);
    }

    function deletePermission(x) {
        let id = $(x).data('panel-id');
        if (!confirm("Delete This Permission?")) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: "{!! route('permission.delete') !!}",
            cache: false,
            data: { _token: "{{ csrf_token() }}", id: id },
            success: function () {
                toastr.success('Permission deleted successfully!');
                $('#permissionTable').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Unable to delete permission.');
            }
        });
    }
</script>
@endsection
