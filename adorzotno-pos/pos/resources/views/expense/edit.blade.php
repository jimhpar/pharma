@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Expense Edit</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Expense Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 20px rgba(15,23,42,.06)">
            <div class="card-header d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,#f8faff,#eef3ff);border-radius:12px 12px 0 0">
                <div>
                    <h5 class="mb-1 fw-bold">Edit Expense</h5>
                    <div class="text-muted small">Update expense details.</div>
                </div>
                <a href="{{ route('expense.show') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('expense.update', $expense->id) }}" enctype="multipart/form-data">
                    @include('expense._form')
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
