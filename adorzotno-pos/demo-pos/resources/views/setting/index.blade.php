@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3></h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">Setting List</h5>
                <a href="{{ route('setting.create') }}" class="text-white"><button class="btn btn-primary fw-bold"><i class="bi bi-plus pe-1 fs-5"></i>Create</button></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="settingTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
    $(document).ready(function () {
        $('#settingTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('setting.list') }}",
                type: "POST",
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                },
            },
            columns: [
                {title: 'Company Name', data: 'company_name', name: 'company_name', className: "text-center", orderable: true, searchable: true},
                {title: 'Header Logo', data: 'header_logo', name: 'header_logo', className: "text-center", orderable: false, searchable: false},
                {title: 'Footer Logo', data: 'footer_logo', name: 'footer_logo', className: "text-center", orderable: false, searchable: false},
                {title: 'Phone', data: 'phone', name: 'phone', className: "text-center", orderable: true, searchable: true},
                {title: 'Email', data: 'email', name: 'email', className: "text-center", orderable: true, searchable: true},
                {title: 'Address', data: 'office_address', name: 'office_address', className: "text-center", orderable: false, searchable: true},
                {title: 'About Text', data: 'homepage_about_text', name: 'homepage_about_text', className: "text-center", orderable: false, searchable: true},
                {title: 'Action', className: "text-center", data: function (data) {
                    return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="' + data.id + '" onclick="editSetting(this)"><i class="fa fa-edit"></i></a>';
                }, orderable: false, searchable: false}
            ]
        });
    });

    function editSetting(x) {
        let btn = $(x).data('panel-id');
        let url = '{{ route("setting.edit", ":id") }}';
        window.location.href = url.replace(':id', btn);
    }

    function deleteSetting(x) {
        let id = $(x).data('panel-id');
        if (!confirm("Delete This Setting?")) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: "{!! route('setting.delete') !!}",
            cache: false,
            data: {_token: "{{ csrf_token() }}", 'id': id},
            success: function () {
                toastr.success('Setting Deleted Successfully!');
                $('#settingTable').DataTable().clear().draw();
            },
        });
    }
</script>
@endsection
