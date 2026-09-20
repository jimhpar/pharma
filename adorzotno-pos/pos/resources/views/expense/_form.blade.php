@csrf
<style>
.exp-form-card { background:#fff; border:1px solid #e9ecef; border-radius:10px; padding:1.25rem; margin-bottom:1rem; }
.exp-form-title { font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#6c757d; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:2px solid #f0f0f0; }
.exp-amount-display { font-size:1.5rem; font-weight:900; color:#dc2626; text-align:center; padding:.5rem; background:#fef2f2; border-radius:8px; margin-bottom:.5rem; }
</style>

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-md-8">
        <div class="exp-form-card">
            <div class="exp-form-title">Expense Details</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Expense Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('expense_date') is-invalid @enderror"
                        name="expense_date"
                        value="{{ old('expense_date', isset($expense) ? $expense->expense_date?->format('Y-m-d') : now()->format('Y-m-d')) }}">
                    @error('expense_date')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">৳</span>
                        <input type="number" step="0.01" min="0.01"
                            class="form-control form-control-lg @error('amount') is-invalid @enderror"
                            name="amount"
                            id="amountInput"
                            value="{{ old('amount', $expense->amount ?? '') }}"
                            placeholder="0.00"
                            style="font-size:1.2rem;font-weight:800">
                    </div>
                    @error('amount')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Expense Category <span class="text-danger">*</span></label>
                    <select class="form-control @error('expense_category_id') is-invalid @enderror" name="expense_category_id">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ (string) old('expense_category_id', $expense->expense_category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('expense_category_id')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Branch <span class="text-danger">*</span></label>
                    <select class="form-control @error('branch_id') is-invalid @enderror" name="branch_id">
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ (string) old('branch_id', $expense->branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Payment Source</label>
                    <input class="form-control @error('payment_source') is-invalid @enderror"
                        name="payment_source"
                        value="{{ old('payment_source', $expense->payment_source ?? '') }}"
                        list="payment-source-list"
                        placeholder="Cash, Bank, bKash, Nagad…">
                    <datalist id="payment-source-list">
                        <option value="Cash">
                        <option value="Bank">
                        <option value="bKash">
                        <option value="Nagad">
                        <option value="Rocket">
                    </datalist>
                    @error('payment_source')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Vendor <span class="fw-normal text-muted">(optional)</span></label>
                    <input class="form-control @error('vendor_name') is-invalid @enderror"
                        name="vendor_name"
                        value="{{ old('vendor_name', $expense->vendor_name ?? '') }}"
                        placeholder="Vendor or payee name">
                    @error('vendor_name')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold mb-1">Note <span class="fw-normal text-muted">(optional)</span></label>
                    <textarea class="form-control @error('note') is-invalid @enderror"
                        name="note" rows="3"
                        placeholder="Purpose, remarks, or additional details…">{{ old('note', $expense->note ?? '') }}</textarea>
                    @error('note')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-md-4">
        <div class="exp-form-card">
            <div class="exp-form-title">Attachment</div>
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">Upload Document <span class="fw-normal text-muted">(optional)</span></label>
                <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                    name="attachment"
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                <div class="form-text text-muted small">Allowed: JPG, PNG, PDF, DOC, XLS</div>
                @error('attachment')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                @if(!empty($expense?->attachment_path))
                <a class="btn btn-outline-info btn-sm mt-2 w-100" target="_blank" href="{{ url($expense->attachment_path) }}">
                    <i class="bi bi-paperclip me-1"></i>View Current Attachment
                </a>
                @endif
            </div>
        </div>

        <div class="exp-form-card">
            <div class="exp-form-title">Summary</div>
            <div id="amountPreview" class="exp-amount-display">
                ৳ {{ number_format((float)old('amount', $expense->amount ?? 0), 2) }}
            </div>
            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn btn-danger fw-bold">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ isset($expense) && $expense->exists ? 'Update Expense' : 'Save Expense' }}
                </button>
                <a href="{{ route('expense.show') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('amountInput')?.addEventListener('input', function () {
    var val = parseFloat(this.value || 0);
    document.getElementById('amountPreview').textContent = '৳ ' + val.toLocaleString('en', {minimumFractionDigits:2,maximumFractionDigits:2});
});
</script>
