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
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Categories</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">
                    Category List
                </h5>
                <a href="{{ route('category.create') }}" class="text-white"><button class="btn btn-primary fw-bold"><i class="bi bi-plus pe-1 fs-5"></i>Create</button></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="categoryTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
    $(document).ready(function () {
    $('#categoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            "url": "{{route('category.list')}}",
            "type": "POST",
            data: function (d) {
                d._token = "{{ csrf_token() }}";
            },
        },
        columns: [           
            {title: 'Serial', data: 'sort_order', name: 'sort_order', className: "text-center", orderable: true, searchable: true},
            {title: 'Category Name', data: 'name', name: 'name', className: "text-center", orderable: true, searchable: true},
            {title: 'Slug', data: 'slug', name: 'slug', className: "text-center", orderable: true, searchable: true},
            {title: 'Icon', data: 'icon', name: 'icon', className: "text-center", orderable: false, searchable: false},
            {title: 'Image', data: 'image', name: 'image', className: "text-center", orderable: false, searchable: false},
            {title: 'Status', data: 'status', name: 'status', className: "text-center", orderable: true, searchable: true}, 
            {title: 'Action', className: "text-center", data: function (data) 
            {
                return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="' + data.id + '" onclick="editCategory(this)"><i class="fa fa-edit"></i></a>'+
                    '<a title="delete" class="btn btn-danger btn-xs" data-panel-id="' + data.id + '" onclick="deleteCategory(this)"><i class="fa fa-trash"></i></a>';
            },
            orderable: false, searchable: false
            } 
           
        ]
    });
});

function editCategory(x) {
    let btn = $(x).data('panel-id');    
    let url = '{{route("category.edit", ":id") }}';
    window.location.href = url.replace(':id', btn);
}

function deleteCategory(x) {
    let id = $(x).data('panel-id');
    if(!confirm("Delete This Category?")){
        return false;
    }
    $.ajax({
        type: 'POST',
        url: "{!! route('category.delete') !!}",
        cache: false,
        data: {_token: "{{csrf_token()}}",'id': id},
        success: function (data) {
            toastr.success('Category Deleted Successfully!');
            $('#categoryTable').DataTable().clear().draw();
        },
    });
}
</script>
@endsection
