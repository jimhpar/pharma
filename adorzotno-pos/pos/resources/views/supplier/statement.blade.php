@extends('layouts.main')
@php
    use App\Support\Currency;
    use App\Support\DateFormatter;
@endphp
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Supplier Statement</h3>
                <p class="text-muted mb-0">{{ $supplier->name }} ledger, due, and aging overview.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('supplier.show') }}">Suppliers</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Statement</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h4 class="card-title mb-1">{{ $supplier->name }}</h4>
                    <p class="text-muted mb-0">
                        {{ $supplier->supplier_code }} |
                        {{ $supplier->phone ?: 'No phone' }} |
                        {{ $supplier->email ?: 'No email' }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('supplier.edit', $supplier->id) }}" class="btn btn-warning">Edit Supplier</a>
                    <a href="{{ route('supplier.show') }}" class="btn btn-light">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Address</div>
                            <div>{{ $supplier->address ?: 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Payment Terms</div>
                            <div>{{ $supplier->payment_terms ?: 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Status</div>
                            <div>{{ ucfirst($supplier->status) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Outstanding Due</div>
                        <h4 class="mt-2 mb-0">{{ Currency::format($outstandingDue) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Opening Balance</div>
                        <h4 class="mt-2 mb-0">{{ Currency::format($supplier->opening_balance) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Credit Limit</div>
                        <h4 class="mt-2 mb-0">{{ $supplier->credit_limit !== null ? Currency::format($supplier->credit_limit) : 'No limit' }}</h4>
                        @if ($availableCredit !== null)
                        <div class="text-muted small mt-2">Available: {{ Currency::format($availableCredit) }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Purchase Value</div>
                        <h4 class="mt-2 mb-0">{{ Currency::format($purchaseGrandTotal) }}</h4>
                        <div class="text-muted small mt-2">Due: {{ Currency::format($purchaseDueTotal) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Direct Supplier Payments</div>
                        <h4 class="mt-2 mb-0">{{ Currency::format($directPaymentTotal) }}</h4>
                        <div class="text-muted small mt-2">{{ $supplierPayments->count() }} payment(s) recorded</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Supplier Returns</div>
                        <h4 class="mt-2 mb-0">{{ Currency::format($supplierReturnTotal) }}</h4>
                        <div class="text-muted small mt-2">{{ $supplier->supplierReturns->count() }} return(s) recorded</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Last Activity</div>
                        <h4 class="mt-2 mb-0">{{ $lastActivityAt ? DateFormatter::date($lastActivityAt) : 'No activity yet' }}</h4>
                        <div class="text-muted small mt-2">Purchases paid: {{ Currency::format($purchasePaidTotal) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Due Aging</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Current</div>
                            <div class="fw-bold mt-2">{{ Currency::format($agingBuckets['current']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">1-30 Days</div>
                            <div class="fw-bold mt-2">{{ Currency::format($agingBuckets['1_30']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">31-60 Days</div>
                            <div class="fw-bold mt-2">{{ Currency::format($agingBuckets['31_60']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">61-90 Days</div>
                            <div class="fw-bold mt-2">{{ Currency::format($agingBuckets['61_90']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">90+ Days</div>
                            <div class="fw-bold mt-2">{{ Currency::format($agingBuckets['90_plus']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="text-muted small">Total Aged Due</div>
                            <div class="fw-bold mt-2">
                                {{ Currency::format($agingBuckets['current'] + $agingBuckets['1_30'] + $agingBuckets['31_60'] + $agingBuckets['61_90'] + $agingBuckets['90_plus']) }}
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-3">
                    Aging is calculated from each purchase order date for balances that still remain due.
                </p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Purchases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Purchase</th>
                                        <th>Date</th>
                                        <th class="text-end">Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($supplier->purchaseOrders->take(5) as $purchaseOrder)
                                    <tr>
                                        <td>{{ $purchaseOrder->purchase_no }}</td>
                                        <td>{{ DateFormatter::date($purchaseOrder->purchase_date) }}</td>
                                        <td class="text-end">{{ Currency::format($purchaseOrder->due_total) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No purchases found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($supplierPayments->take(5) as $payment)
                                    <tr>
                                        <td>{{ $payment->reference_no ?: ('PAY-' . $payment->id) }}</td>
                                        <td>{{ DateFormatter::date($payment->payment_date) }}</td>
                                        <td class="text-end">{{ Currency::format($payment->amount) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No direct payments found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Returns</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Return</th>
                                        <th>Date</th>
                                        <th class="text-end">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($supplier->supplierReturns->take(5) as $supplierReturn)
                                    <tr>
                                        <td>{{ $supplierReturn->return_no }}</td>
                                        <td>{{ DateFormatter::date($supplierReturn->return_date) }}</td>
                                        <td class="text-end">{{ Currency::format($supplierReturn->items->sum(fn ($item) => (float) $item->unit_cost * (float) $item->quantity)) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No supplier returns found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Ledger Statement</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Note</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledgerEntries as $entry)
                            <tr>
                                <td>{{ DateFormatter::date($entry['date']) }}</td>
                                <td>{{ $entry['reference'] }}</td>
                                <td>{{ $entry['type'] }}</td>
                                <td>{{ $entry['note'] }}</td>
                                <td class="text-end">{{ Currency::format($entry['debit']) }}</td>
                                <td class="text-end">{{ Currency::format($entry['credit']) }}</td>
                                <td class="text-end fw-bold">{{ Currency::format($entry['balance']) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No ledger activity found for this supplier yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
