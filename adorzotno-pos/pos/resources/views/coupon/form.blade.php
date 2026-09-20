@php
    $startValue = old('start_at', $coupon?->start_at?->format('Y-m-d\TH:i'));
    $endValue = old('end_at', $coupon?->end_at?->format('Y-m-d\TH:i'));
@endphp

<div class="row">
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="code">Code</label>
            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $coupon?->code) }}" placeholder="SAVE10">
            @error('code')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="discount_type">Discount Type</label>
            <select class="form-control @error('discount_type') is-invalid @enderror" name="discount_type">
                <option value="">Select Type</option>
                <option value="fixed" {{ old('discount_type', $coupon?->discount_type) === 'fixed' ? 'selected' : '' }}>Fixed</option>
                <option value="percent" {{ old('discount_type', $coupon?->discount_type) === 'percent' ? 'selected' : '' }}>Percent</option>
            </select>
            @error('discount_type')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="discount_value">Discount Value</label>
            <input type="number" step="0.01" min="0" class="form-control @error('discount_value') is-invalid @enderror" name="discount_value" value="{{ old('discount_value', $coupon?->discount_value) }}" placeholder="10.00">
            @error('discount_value')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="min_order_amount">Minimum Order Amount</label>
            <input type="number" step="0.01" min="0" class="form-control @error('min_order_amount') is-invalid @enderror" name="min_order_amount" value="{{ old('min_order_amount', $coupon?->min_order_amount) }}" placeholder="Optional">
            @error('min_order_amount')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="max_discount_amount">Maximum Discount Amount</label>
            <input type="number" step="0.01" min="0" class="form-control @error('max_discount_amount') is-invalid @enderror" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon?->max_discount_amount) }}" placeholder="Optional">
            @error('max_discount_amount')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="usage_limit">Usage Limit</label>
            <input type="number" min="1" class="form-control @error('usage_limit') is-invalid @enderror" name="usage_limit" value="{{ old('usage_limit', $coupon?->usage_limit) }}" placeholder="Unlimited">
            @error('usage_limit')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="start_at">Starts At</label>
            <input type="datetime-local" class="form-control @error('start_at') is-invalid @enderror" name="start_at" value="{{ $startValue }}">
            @error('start_at')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="end_at">Ends At</label>
            <input type="datetime-local" class="form-control @error('end_at') is-invalid @enderror" name="end_at" value="{{ $endValue }}">
            @error('end_at')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control @error('status') is-invalid @enderror" name="status">
                <option value="">Select Status</option>
                <option value="active" {{ old('status', $coupon?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $coupon?->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    @if($coupon)
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label>Used Count</label>
            <input type="text" class="form-control" value="{{ (int) $coupon->used_count }}" readonly>
        </div>
    </div>
    @endif

    <div class="col-12 d-flex justify-content-end">
        <a href="{{ route('coupon.show') }}" class="btn btn-light-secondary me-1 mb-1">Cancel</a>
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
