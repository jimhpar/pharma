@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>New Purchase Requisition</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchaseRequisition.show') }}">Purchase Requisitions</a></li>
                        <li class="breadcrumb-item active">New</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <form method="POST" action="{{ route('purchaseRequisition.store') }}">
            @csrf
            <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 6px rgba(0,0,0,.05)">
                <div class="card-header d-flex align-items-center justify-content-between" style="border-bottom:1px solid #e2e8f0;padding:.85rem 1.25rem">
                    <h5 class="card-title mb-0" style="font-size:.95rem;font-weight:700;color:#1e293b">
                        <i class="bi bi-card-checklist me-2 text-primary"></i>Purchase Requisition Details
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('purchaseRequisition.show') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                            <i class="bi bi-save me-1"></i>Save
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @include('purchase_requisition._form')
                </div>
            </div>
        </form>
    </section>
</div>
@endsection
