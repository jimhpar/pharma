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
                        <li class="breadcrumb-item active" aria-current="page">Reviews</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">Product Review List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reviewTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
    $(document).ready(function () {
        $('#reviewTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('review.list') }}",
                type: "POST",
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                },
            },
            columns: [
                {title: 'ID', data: 'id', name: 'id', className: 'text-center', orderable: true, searchable: true},
                {title: 'Product', data: 'product', name: 'product', className: 'text-center', orderable: false, searchable: true},
                {title: 'Customer', data: 'customer', name: 'customer', className: 'text-center', orderable: false, searchable: true},
                {title: 'Rating', data: 'rating', name: 'rating', className: 'text-center', orderable: true, searchable: false},
                {title: 'Title', data: 'title', name: 'title', className: 'text-center', orderable: false, searchable: true},
                {title: 'Comment', data: 'comment', name: 'comment', className: 'text-start', orderable: false, searchable: true},
                {title: 'Verified', data: 'verified_purchase', name: 'verified_purchase', className: 'text-center', orderable: false, searchable: false},
                {title: 'Status', data: 'status_badge', name: 'status', className: 'text-center', orderable: true, searchable: true},
                {title: 'Approved At', data: 'approved_date', name: 'approved_at', className: 'text-center', orderable: true, searchable: false},
                {title: 'Action', className: 'text-center', data: function (data) {
                    if ((data.status || '').toLowerCase() === 'approved') {
                        return '<span class="badge bg-light-success">Approved</span>';
                    }

                    return '<button type="button" class="btn btn-success btn-sm" data-panel-id="' + data.id + '" onclick="approveReview(this)">Approve</button>';
                }, orderable: false, searchable: false}
            ]
        });
    });

    function approveReview(x) {
        let id = $(x).data('panel-id');
        if (!confirm("Approve this review?")) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: "{!! route('review.approve') !!}",
            cache: false,
            data: {_token: "{{ csrf_token() }}", id: id},
            success: function () {
                toastr.success('Review approved successfully!');
                $('#reviewTable').DataTable().clear().draw();
            },
        });
    }
</script>
@endsection
