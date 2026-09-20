@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Manual Point Adjustment</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('loyalty.settings') }}">Loyalty</a></li>
                        <li class="breadcrumb-item active">Adjust Points</li>
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
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-sliders-h me-2 text-primary"></i>Adjust Customer Points</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('loyalty.adjust.store') }}" method="POST" id="adjustForm">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="customer_id">Customer *</label>
                                <select class="form-select @error('customer_id') is-invalid @enderror"
                                    id="customer_id" name="customer_id" required>
                                    <option value="">— Select Customer —</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            data-points="{{ $customer->loyalty_points }}"
                                            data-member="{{ $customer->is_member ? 'Member' : 'Regular' }}"
                                            {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                            @if ($customer->phone) ({{ $customer->phone }}) @endif
                                            — {{ number_format($customer->loyalty_points) }} pts
                                            @if ($customer->is_member) ★ @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="customerPointsInfo" class="mt-2 d-none">
                                    <span class="badge bg-primary fs-6" id="currentPointsDisplay"></span>
                                    <span class="badge bg-secondary ms-1" id="memberStatusDisplay"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="adjustment">
                                    Points Adjustment *
                                </label>
                                <input type="number" class="form-control @error('adjustment') is-invalid @enderror"
                                    id="adjustment" name="adjustment"
                                    value="{{ old('adjustment') }}"
                                    placeholder="e.g. 50 to add, -30 to remove"
                                    required>
                                <div class="form-text">Use positive number to add points, negative to remove.</div>
                                @error('adjustment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="newBalancePreview" class="mt-1 text-muted small d-none">
                                    New balance will be: <strong id="newBalanceValue">—</strong> points
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="note">Reason / Note *</label>
                                <textarea class="form-control @error('note') is-invalid @enderror"
                                    id="note" name="note" rows="3"
                                    placeholder="e.g. Correction for previous order, promotional bonus..."
                                    required>{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Apply Adjustment
                                </button>
                                <a href="{{ route('loyalty.report') }}" class="btn btn-outline-secondary">
                                    View Ledger
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-history me-2 text-info"></i>Recent Adjustments</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-center">Points</th>
                                        <th>Note</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $recentAdjustments = \App\Models\LoyaltyPointLedger::query()
                                            ->with('customer:id,name')
                                            ->where('type', 'adjusted')
                                            ->orderByDesc('created_at')
                                            ->limit(10)
                                            ->get();
                                    @endphp
                                    @forelse ($recentAdjustments as $entry)
                                        <tr>
                                            <td>{{ $entry->customer?->name ?? '—' }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $entry->points > 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ $entry->note }}</td>
                                            <td class="small">{{ \App\Support\DateFormatter::shortDateTime($entry->created_at) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No adjustments yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
    const customerSelect = document.getElementById('customer_id');
    const adjustmentInput = document.getElementById('adjustment');
    const infoBox = document.getElementById('customerPointsInfo');
    const currentPts = document.getElementById('currentPointsDisplay');
    const memberBadge = document.getElementById('memberStatusDisplay');
    const previewBox = document.getElementById('newBalancePreview');
    const previewVal = document.getElementById('newBalanceValue');

    customerSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) { infoBox.classList.add('d-none'); return; }
        currentPts.textContent = opt.dataset.points + ' pts current balance';
        memberBadge.textContent = opt.dataset.member;
        infoBox.classList.remove('d-none');
        updatePreview();
    });

    adjustmentInput.addEventListener('input', updatePreview);

    function updatePreview() {
        const opt = customerSelect.options[customerSelect.selectedIndex];
        if (!opt || !opt.value) { previewBox.classList.add('d-none'); return; }
        const current = parseInt(opt.dataset.points, 10) || 0;
        const adj = parseInt(adjustmentInput.value, 10);
        if (isNaN(adj)) { previewBox.classList.add('d-none'); return; }
        const newBal = current + adj;
        previewVal.textContent = newBal < 0 ? 'Invalid (cannot go below 0)' : newBal;
        previewVal.className = newBal < 0 ? 'text-danger fw-bold' : 'fw-bold';
        previewBox.classList.remove('d-none');
    }
</script>
@endsection
