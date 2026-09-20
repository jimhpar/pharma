@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>VAT Rule</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">VAT Rule</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">VAT Rule List</h5>
                <a href="{{ route('taxRule.create') }}" class="text-white"><button class="btn btn-primary fw-bold"><i class="bi bi-plus pe-1 fs-5"></i>Create</button></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="taxRuleTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
    $(document).ready(function () {
        $('#taxRuleTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                "url": "{{ route('taxRule.list') }}",
                "type": "POST",
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                },
            },
            columns: [
                {title: 'Name', data: 'name', name: 'name', className: "text-center", orderable: true, searchable: true},
                {title: 'Rate', data: 'rate_percent', name: 'rate_percent', className: "text-center", orderable: true, searchable: true},
                {title: 'Inclusive', data: 'is_inclusive', name: 'is_inclusive', className: "text-center", orderable: true, searchable: true},
                {title: 'Status', data: 'status', name: 'status', className: "text-center", orderable: true, searchable: true},
                {title: 'Action', className: "text-center", data: function (data)
                {
                    return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="' + data.id + '" onclick="editTaxRule(this)"><i class="fa fa-edit"></i></a>'+
                        '<a title="delete" class="btn btn-danger btn-xs" data-panel-id="' + data.id + '" onclick="deleteTaxRule(this)"><i class="fa fa-trash"></i></a>';
                },
                orderable: false, searchable: false
                }
            ]
        });
    });

    function editTaxRule(x) {
        let btn = $(x).data('panel-id');
        let url = '{{ route("taxRule.edit", ":id") }}';
        window.location.href = url.replace(':id', btn);
    }

    function deleteTaxRule(x) {
        let id = $(x).data('panel-id');
        if(!confirm("Delete This VAT Rule?")){
            return false;
        }
        $.ajax({
            type: 'POST',
            url: "{!! route('taxRule.delete') !!}",
            cache: false,
            data: {_token: "{{ csrf_token() }}",'id': id},
            success: function () {
                toastr.success('VAT Rule Deleted Successfully!');
                $('#taxRuleTable').DataTable().clear().draw();
            },
        });
    }
</script>

@endsection
