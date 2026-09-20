@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Edit Purchase Order</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchaseOrder.show') }}">Purchase Orders</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section>
        <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 6px rgba(0,0,0,.05)">
            <div class="card-header d-flex align-items-center justify-content-between" style="border-bottom:1px solid #e2e8f0;padding:.85rem 1.25rem">
                <h5 class="mb-0" style="font-size:.95rem;font-weight:700;color:#1e293b">
                    <i class="bi bi-pencil-square me-2 text-warning"></i>
                    {{ $purchaseOrder->purchase_no }}
                </h5>
                <a href="{{ route('purchaseOrder.receive', $purchaseOrder->id) }}" class="btn btn-primary btn-sm fw-bold">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Receive Stock
                </a>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('purchaseOrder.update', $purchaseOrder->id) }}">
                    @csrf
                    @include('purchase_order._form')
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
