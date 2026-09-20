@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Branch Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Branch Create</li>
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
                        <h4 class="card-title">Branch Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('branch.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}">
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
                                            <label>Code</label>
                                            <input class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}">
                                            @error('code')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Invoice Prefix</label>
                                            <input class="form-control @error('invoice_prefix') is-invalid @enderror" name="invoice_prefix" value="{{ old('invoice_prefix') }}">
                                            @error('invoice_prefix')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}">
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
                                            <label>Email</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
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
                                            <label>Status</label>
                                            <select class="form-control @error('status') is-invalid @enderror" name="status">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('status')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h5>Default Warehouse</h5>
                                        <p class="text-muted mb-3">Every branch starts with one default warehouse.</p>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Default Warehouse Name</label>
                                            <input class="form-control @error('default_warehouse_name') is-invalid @enderror" name="default_warehouse_name" value="{{ old('default_warehouse_name') }}">
                                            @error('default_warehouse_name')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Default Warehouse Code</label>
                                            <input class="form-control @error('default_warehouse_code') is-invalid @enderror" name="default_warehouse_code" value="{{ old('default_warehouse_code') }}">
                                            @error('default_warehouse_code')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Allow Negative Stock</label>
                                            <select class="form-control @error('default_allow_negative_stock') is-invalid @enderror" name="default_allow_negative_stock">
                                                <option value="no" {{ old('default_allow_negative_stock', 'no') === 'no' ? 'selected' : '' }}>No</option>
                                                <option value="yes" {{ old('default_allow_negative_stock') === 'yes' ? 'selected' : '' }}>Yes</option>
                                            </select>
                                            @error('default_allow_negative_stock')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12 col-12">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="4">{{ old('address') }}</textarea>
                                            @error('address')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12 col-12">
                                        <div class="form-group">
                                            <label>Default Warehouse Address</label>
                                            <textarea class="form-control @error('default_warehouse_address') is-invalid @enderror" name="default_warehouse_address" rows="3">{{ old('default_warehouse_address') }}</textarea>
                                            @error('default_warehouse_address')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
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
