@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Branch Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Branch Edit</li>
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
                        <h4 class="card-title">Branch Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('branch.update', $branch->id) }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name) }}">
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
                                            <input class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $branch->code) }}">
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
                                            <input class="form-control @error('invoice_prefix') is-invalid @enderror" name="invoice_prefix" value="{{ old('invoice_prefix', $branch->invoice_prefix) }}">
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
                                            <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $branch->phone) }}">
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
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $branch->email) }}">
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
                                                <option value="active" {{ old('status', $branch->is_active ? 'active' : 'inactive') === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $branch->is_active ? 'active' : 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Current Default Warehouse</label>
                                            <select class="form-control @error('default_warehouse_id') is-invalid @enderror" name="default_warehouse_id">
                                                <option value="">Select Warehouse</option>
                                                @foreach ($branch->warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}" {{ (string) old('default_warehouse_id', $branch->defaultWarehouse?->id) === (string) $warehouse->id ? 'selected' : '' }}>
                                                        {{ $warehouse->name }} ({{ $warehouse->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('default_warehouse_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>New Default Warehouse Name</label>
                                            <input class="form-control @error('new_default_warehouse_name') is-invalid @enderror" name="new_default_warehouse_name" value="{{ old('new_default_warehouse_name') }}" placeholder="Optional">
                                            @error('new_default_warehouse_name')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>New Default Warehouse Code</label>
                                            <input class="form-control @error('new_default_warehouse_code') is-invalid @enderror" name="new_default_warehouse_code" value="{{ old('new_default_warehouse_code') }}" placeholder="Optional">
                                            @error('new_default_warehouse_code')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Allow Negative Stock For New Warehouse</label>
                                            <select class="form-control @error('new_default_allow_negative_stock') is-invalid @enderror" name="new_default_allow_negative_stock">
                                                <option value="no" {{ old('new_default_allow_negative_stock', 'no') === 'no' ? 'selected' : '' }}>No</option>
                                                <option value="yes" {{ old('new_default_allow_negative_stock') === 'yes' ? 'selected' : '' }}>Yes</option>
                                            </select>
                                            @error('new_default_allow_negative_stock')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12 col-12">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="4">{{ old('address', $branch->address) }}</textarea>
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
                                            <label>New Default Warehouse Address</label>
                                            <textarea class="form-control @error('new_default_warehouse_address') is-invalid @enderror" name="new_default_warehouse_address" rows="3">{{ old('new_default_warehouse_address') }}</textarea>
                                            @error('new_default_warehouse_address')
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
