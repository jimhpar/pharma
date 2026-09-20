@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Supplier Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Supplier Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section id="multiple-column-form">
        <div class="row match-height">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Supplier Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('supplier.update', $supplier->id) }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Supplier Code</label>
                                            <input type="text" class="form-control @error('supplier_code') is-invalid @enderror" placeholder="Enter Supplier Code" name="supplier_code" value="{{ old('supplier_code', $supplier->supplier_code) }}">
                                            @error('supplier_code')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Supplier Name</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" placeholder="Enter Supplier Name" name="name" value="{{ old('name', $supplier->name) }}">
                                            @error('name')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Supplier Email</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter Supplier Email" name="email" value="{{ old('email', $supplier->email) }}">
                                            @error('email')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Supplier Phone</label>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" placeholder="Enter Supplier Phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
                                            @error('phone')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Payment Terms</label>
                                            <input type="text" class="form-control @error('payment_terms') is-invalid @enderror" placeholder="Enter Payment Terms" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}">
                                            @error('payment_terms')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Opening Balance</label>
                                            <input type="number" step="0.01" min="0" class="form-control @error('opening_balance') is-invalid @enderror" placeholder="Enter Opening Balance" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance) }}">
                                            @error('opening_balance')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Credit Limit</label>
                                            <input type="number" step="0.01" min="0" class="form-control @error('credit_limit') is-invalid @enderror" placeholder="Enter Credit Limit" name="credit_limit" value="{{ old('credit_limit', $supplier->credit_limit) }}">
                                            @error('credit_limit')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select class="form-control @error('status') is-invalid @enderror" name="status">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status', $supplier->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $supplier->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('status')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12 col-12">
                                        <div class="form-group">
                                            <label>Supplier Address</label>
                                            <textarea class="form-control @error('address') is-invalid @enderror" placeholder="Enter Supplier Address" name="address" rows="4">{{ old('address', $supplier->address) }}</textarea>
                                            @error('address')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
   
</script>

@endsection
