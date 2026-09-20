<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $salesCommissionPlan->name ?? '') }}">
            @error('name')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Code</label>
            <input class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $salesCommissionPlan->code ?? '') }}">
            @error('code')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Calculation Type</label>
            <select class="form-control @error('calculation_type') is-invalid @enderror" name="calculation_type">
                @foreach (['percentage' => 'Percentage', 'fixed' => 'Fixed Amount'] as $value => $label)
                    <option value="{{ $value }}" {{ old('calculation_type', $salesCommissionPlan->calculation_type ?? 'percentage') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('calculation_type')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Rate</label>
            <input type="number" step="0.01" min="0" class="form-control @error('rate') is-invalid @enderror" name="rate" value="{{ old('rate', $salesCommissionPlan->rate ?? '0.00') }}">
            @error('rate')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Base Amount Type</label>
            <select class="form-control @error('base_amount_type') is-invalid @enderror" name="base_amount_type">
                @foreach (['gross_sale' => 'Gross Sale', 'net_sale' => 'Net Sale', 'profit' => 'Profit'] as $value => $label)
                    <option value="{{ $value }}" {{ old('base_amount_type', $salesCommissionPlan->base_amount_type ?? 'net_sale') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('base_amount_type')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Apply Scope</label>
            <select class="form-control @error('apply_scope') is-invalid @enderror" name="apply_scope">
                @foreach (['invoice' => 'Invoice', 'item' => 'Item'] as $value => $label)
                    <option value="{{ $value }}" {{ old('apply_scope', $salesCommissionPlan->apply_scope ?? 'invoice') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('apply_scope')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Minimum Target Amount</label>
            <input type="number" step="0.01" min="0" class="form-control @error('min_target_amount') is-invalid @enderror" name="min_target_amount" value="{{ old('min_target_amount', $salesCommissionPlan->min_target_amount ?? '') }}">
            @error('min_target_amount')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Maximum Commission Amount</label>
            <input type="number" step="0.01" min="0" class="form-control @error('max_commission_amount') is-invalid @enderror" name="max_commission_amount" value="{{ old('max_commission_amount', $salesCommissionPlan->max_commission_amount ?? '') }}">
            @error('max_commission_amount')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Status</label>
            <select class="form-control @error('status') is-invalid @enderror" name="status">
                <option value="active" {{ old('status', ($salesCommissionPlan->is_active ?? true) ? 'active' : 'inactive') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', ($salesCommissionPlan->is_active ?? true) ? 'active' : 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
