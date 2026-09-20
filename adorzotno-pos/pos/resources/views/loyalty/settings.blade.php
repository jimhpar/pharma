@extends('layouts.main')
@php
    $loyaltySettings = is_array($loyaltySettings ?? null) ? $loyaltySettings : [
        'enabled' => true,
        'redemption_enabled' => true,
        'points_per_amount' => 100,
        'point_value' => 1,
        'membership_threshold' => 5000,
    ];
@endphp
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Loyalty Settings</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Loyalty Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-star text-warning me-2"></i>Loyalty Program Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('loyalty.settings.save') }}" method="POST">
                            @csrf

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <h6 class="fw-bold text-muted text-uppercase small mb-3">System Toggles</h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="loyalty_enabled"
                                            name="loyalty_enabled" value="1"
                                            {{ $loyaltySettings['enabled'] ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="loyalty_enabled">
                                            Enable Loyalty Point System
                                        </label>
                                        <div class="text-muted small">Customers earn points on every purchase.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="loyalty_redemption_enabled"
                                            name="loyalty_redemption_enabled" value="1"
                                            {{ $loyaltySettings['redemption_enabled'] ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="loyalty_redemption_enabled">
                                            Enable Point Redemption
                                        </label>
                                        <div class="text-muted small">Allow customers to redeem points as discount.</div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Point Earning Rule</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="loyalty_points_per_amount">
                                        Purchase Amount Per Point (৳)
                                    </label>
                                    <input type="number" class="form-control @error('loyalty_points_per_amount') is-invalid @enderror"
                                        id="loyalty_points_per_amount" name="loyalty_points_per_amount"
                                        value="{{ old('loyalty_points_per_amount', $loyaltySettings['points_per_amount']) }}"
                                        min="1" step="1">
                                    <div class="form-text">e.g. 100 means every ৳100 spent = 1 point.</div>
                                    @error('loyalty_points_per_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="loyalty_point_value">
                                        Value of 1 Point (৳)
                                    </label>
                                    <input type="number" class="form-control @error('loyalty_point_value') is-invalid @enderror"
                                        id="loyalty_point_value" name="loyalty_point_value"
                                        value="{{ old('loyalty_point_value', $loyaltySettings['point_value']) }}"
                                        min="0.01" step="0.01">
                                    <div class="form-text">e.g. 1 means 1 point = ৳1 discount.</div>
                                    @error('loyalty_point_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Membership Threshold</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="loyalty_membership_threshold">
                                        Minimum Total Purchase to Become Member (৳)
                                    </label>
                                    <input type="number" class="form-control @error('loyalty_membership_threshold') is-invalid @enderror"
                                        id="loyalty_membership_threshold" name="loyalty_membership_threshold"
                                        value="{{ old('loyalty_membership_threshold', $loyaltySettings['membership_threshold']) }}"
                                        min="0" step="1">
                                    <div class="form-text">e.g. 5000 means customer becomes a member after ৳5,000 in total purchases.</div>
                                    @error('loyalty_membership_threshold')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Settings
                                </button>
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-warning">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h6 class="card-title mb-0"><i class="fas fa-info-circle text-warning me-2"></i>How It Works</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2"><i class="fas fa-circle text-success me-2" style="font-size:0.5rem;vertical-align:middle"></i>Customer earns points based on the final paid amount.</li>
                            <li class="mb-2"><i class="fas fa-circle text-success me-2" style="font-size:0.5rem;vertical-align:middle"></i>Points are calculated using floor: ৳850 ÷ 100 = 8 points.</li>
                            <li class="mb-2"><i class="fas fa-circle text-success me-2" style="font-size:0.5rem;vertical-align:middle"></i>Redeemed points reduce the payable amount before payment.</li>
                            <li class="mb-2"><i class="fas fa-circle text-success me-2" style="font-size:0.5rem;vertical-align:middle"></i>Customer is promoted to Member automatically when total purchase crosses the threshold.</li>
                            <li class="mb-0"><i class="fas fa-circle text-success me-2" style="font-size:0.5rem;vertical-align:middle"></i>All changes to points are recorded in a ledger.</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Quick Links</h6>
                    </div>
                    <div class="card-body p-2">
                        <a href="{{ route('loyalty.adjust') }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                            <i class="fas fa-sliders-h me-1"></i> Manual Point Adjustment
                        </a>
                        <a href="{{ route('loyalty.report') }}" class="btn btn-outline-info btn-sm w-100">
                            <i class="fas fa-chart-bar me-1"></i> Loyalty Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
