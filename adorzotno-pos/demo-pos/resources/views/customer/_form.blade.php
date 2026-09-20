<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Customer Code</label>
            <input class="form-control @error('customer_code') is-invalid @enderror" name="customer_code" value="{{ old('customer_code', $customer->customer_code ?? $suggestedCustomerCode ?? '') }}" placeholder="Enter customer code">
            @error('customer_code')
            <div class="invalid-feedback d-block">
                <i class="bx bx-radio-circle"></i>
                <strong>{{ $message }}</strong>
            </div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $customer->name ?? '') }}" placeholder="Enter customer name">
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
            <label>Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $customer->email ?? '') }}" placeholder="Enter customer email">
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
            <label>Phone</label>
            <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="Enter customer phone">
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
            <label>Customer Group</label>
            <select class="form-control @error('customer_group_id') is-invalid @enderror" name="customer_group_id">
                <option value="">Select Group</option>
                @foreach ($customerGroups as $customerGroup)
                <option value="{{ $customerGroup->id }}" {{ (string) old('customer_group_id', $customer->customer_group_id ?? '') === (string) $customerGroup->id ? 'selected' : '' }}>
                    {{ $customerGroup->name }}
                </option>
                @endforeach
            </select>
            @error('customer_group_id')
            <div class="invalid-feedback d-block">
                <i class="bx bx-radio-circle"></i>
                <strong>{{ $message }}</strong>
            </div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Loyalty Points</label>
            <input type="number" min="0" class="form-control @error('loyalty_points') is-invalid @enderror" name="loyalty_points" value="{{ old('loyalty_points', $customer->loyalty_points ?? 0) }}" placeholder="0">
            @error('loyalty_points')
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
            <input type="number" step="0.01" min="0" class="form-control @error('opening_balance') is-invalid @enderror" name="opening_balance" value="{{ old('opening_balance', $customer->opening_balance ?? 0) }}" placeholder="0.00">
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
            <input type="number" step="0.01" min="0" class="form-control @error('credit_limit') is-invalid @enderror" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? '') }}" placeholder="Optional credit limit">
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
                <option value="active" {{ old('status', $customer->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $customer->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
            <label>Billing Address</label>
            <textarea class="form-control @error('billing_address') is-invalid @enderror" name="billing_address" rows="3" placeholder="Enter billing address">{{ old('billing_address', $customer->billing_address ?? '') }}</textarea>
            @error('billing_address')
            <div class="invalid-feedback d-block">
                <i class="bx bx-radio-circle"></i>
                <strong>{{ $message }}</strong>
            </div>
            @enderror
        </div>
    </div>

    <div class="col-md-12 col-12">
        <div class="form-group">
            <label>Shipping Address</label>
            <textarea class="form-control @error('shipping_address') is-invalid @enderror" name="shipping_address" rows="3" placeholder="Enter shipping address">{{ old('shipping_address', $customer->shipping_address ?? '') }}</textarea>
            @error('shipping_address')
            <div class="invalid-feedback d-block">
                <i class="bx bx-radio-circle"></i>
                <strong>{{ $message }}</strong>
            </div>
            @enderror
        </div>
    </div>

    <div class="col-md-12 col-12">
        <div class="form-group">
            <label><i class="fas fa-sticky-note me-1 text-muted"></i>Customer Note <small class="text-muted">(permanent note, visible in POS)</small></label>
            <textarea class="form-control @error('note') is-invalid @enderror" name="note" rows="3" placeholder="e.g. Diabetic patient, prefers strips, Doctor: Dr. Ahmed...">{{ old('note', $customer->note ?? '') }}</textarea>
            @error('note')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
