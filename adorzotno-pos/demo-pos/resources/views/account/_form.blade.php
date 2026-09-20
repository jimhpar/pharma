@csrf
@php $currentType = old('account_type', $account->account_type ?? request('type', '')); @endphp
<style>
.acc-section { background:#fff; border:1px solid #e9ecef; border-radius:10px; padding:1.25rem; margin-bottom:1rem; }
.acc-section-title { font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#6c757d; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:2px solid #f0f0f0; }
.bank-fields { display:none; }
.bank-fields.visible { display:block; }
.bank-info-banner { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.82rem; color:#1d4ed8; }
</style>

<div class="row g-3">
    {{-- Left: Basic Info --}}
    <div class="col-md-8">
        <div class="acc-section">
            <div class="acc-section-title">Account Information</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Account Type <span class="text-danger">*</span></label>
                    <select class="form-control @error('account_type') is-invalid @enderror" name="account_type" id="accountTypeSelect">
                        <option value="">Select Type</option>
                        @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ $currentType === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('account_type')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Account Code</label>
                    <input class="form-control @error('account_code') is-invalid @enderror" name="account_code"
                        value="{{ old('account_code', $account->account_code ?? '') }}"
                        placeholder="e.g. CASH, BANK-01">
                    @error('account_code')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold mb-1">Account Name <span class="text-danger">*</span></label>
                    <input class="form-control @error('account_name') is-invalid @enderror" name="account_name"
                        value="{{ old('account_name', $account->account_name ?? '') }}"
                        placeholder="e.g. Main Savings Account, Petty Cash">
                    @error('account_name')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Opening Balance</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" step="0.01" class="form-control @error('opening_balance') is-invalid @enderror"
                            name="opening_balance"
                            value="{{ old('opening_balance', $account->opening_balance ?? 0) }}">
                    </div>
                    @error('opening_balance')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Status</label>
                    <select class="form-control @error('status') is-invalid @enderror" name="status">
                        @php $status = old('status', $account->status ?? 'active'); @endphp
                        <option value="active"   {{ $status === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
            </div>
        </div>

        {{-- Bank-specific fields (shown only for bank/mobile_banking) --}}
        <div class="acc-section bank-fields {{ in_array($currentType, ['bank','mobile_banking']) ? 'visible' : '' }}" id="bankFieldsSection">
            <div class="bank-info-banner">
                <i class="bi bi-info-circle me-2"></i>
                Fill in the bank details for proper account identification and reconciliation.
            </div>
            <div class="acc-section-title">Bank Details</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Bank Name</label>
                    <input class="form-control @error('bank_name') is-invalid @enderror" name="bank_name"
                        value="{{ old('bank_name', $account->bank_name ?? '') }}"
                        placeholder="e.g. Dutch Bangla Bank, bKash, Nagad">
                    @error('bank_name')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Account Number</label>
                    <input class="form-control @error('account_number') is-invalid @enderror" name="account_number"
                        value="{{ old('account_number', $account->account_number ?? '') }}"
                        placeholder="e.g. 1234567890">
                    @error('account_number')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Bank Branch</label>
                    <input class="form-control @error('bank_branch') is-invalid @enderror" name="bank_branch"
                        value="{{ old('bank_branch', $account->bank_branch ?? '') }}"
                        placeholder="e.g. Gulshan Branch">
                    @error('bank_branch')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Routing Number</label>
                    <input class="form-control @error('routing_number') is-invalid @enderror" name="routing_number"
                        value="{{ old('routing_number', $account->routing_number ?? '') }}"
                        placeholder="9-digit routing number">
                    @error('routing_number')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Notes + Actions --}}
    <div class="col-md-4">
        <div class="acc-section">
            <div class="acc-section-title">Notes</div>
            <textarea class="form-control @error('notes') is-invalid @enderror"
                name="notes" rows="4"
                placeholder="Any additional notes, signatories, contact info...">{{ old('notes', $account->notes ?? '') }}</textarea>
            @error('notes')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
        </div>

        <div class="acc-section">
            <div class="acc-section-title">Actions</div>
            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn btn-primary fw-semibold">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ isset($account) && $account->exists ? 'Update Account' : 'Save Account' }}
                </button>
                <a href="{{ route('account.banks') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-bank me-1"></i>Back to Bank Accounts
                </a>
                <a href="{{ route('account.show') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list me-1"></i>Chart of Accounts
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('accountTypeSelect')?.addEventListener('change', function () {
    var bankTypes = ['bank', 'mobile_banking'];
    var section = document.getElementById('bankFieldsSection');
    if (section) {
        section.classList.toggle('visible', bankTypes.includes(this.value));
    }
});
</script>
