@extends('layouts.main')
@php
    use App\Support\Currency;
    use App\Support\DateFormatter;
    $initials = collect(explode(' ', $customer->name))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
@endphp
@section('main.content')
<style>
.cs-page { max-width: 100%; }

/* ── Profile header ── */
.cs-profile {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px;
    padding: 1.75rem 2rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.cs-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 800; color: #fff;
    flex-shrink: 0; border: 3px solid rgba(255,255,255,.2);
}
.cs-profile-info { flex: 1; min-width: 0; }
.cs-profile-name { font-size: 1.5rem; font-weight: 800; margin: 0 0 .35rem; }
.cs-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
    border-radius: 20px; padding: 2px 10px;
    font-size: .72rem; font-weight: 600; color: rgba(255,255,255,.85);
    margin: 0 4px 4px 0;
}
.cs-chip i { font-size: .7rem; opacity: .7; }
.cs-badge-mem {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #059669; border-radius: 20px;
    padding: 3px 11px; font-size: .72rem; font-weight: 800; color: #fff;
}
.cs-badge-reg {
    display: inline-flex; align-items: center; gap: .3rem;
    background: rgba(255,255,255,.15); border-radius: 20px;
    padding: 3px 11px; font-size: .72rem; font-weight: 700; color: rgba(255,255,255,.7);
}
.cs-badge-active { background:#16a34a; color:#fff; border-radius:20px; padding:3px 11px; font-size:.7rem; font-weight:800; }
.cs-badge-inactive { background:#dc2626; color:#fff; border-radius:20px; padding:3px 11px; font-size:.7rem; font-weight:800; }

/* ── Stat cards ── */
.cs-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem 1.2rem;
    height: 100%;
    position: relative;
    overflow: hidden;
    transition: box-shadow .15s;
}
.cs-stat:hover { box-shadow: 0 4px 20px rgba(15,23,42,.1); }
.cs-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; margin-bottom: .65rem;
}
.cs-stat-label { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .3rem; }
.cs-stat-value { font-size: 1.3rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.cs-stat-sub { font-size: .72rem; color: #64748b; margin-top: .3rem; }

/* ── Section title ── */
.cs-section-title {
    font-size: .65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .1em; color: #94a3b8; margin: 0 0 .85rem;
    display: flex; align-items: center; gap: .5rem;
}
.cs-section-title::after {
    content: ''; flex: 1; height: 1px; background: #e2e8f0;
}

/* ── Aging ── */
.cs-aging {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.2rem 1.4rem;
    margin-bottom: 1.5rem;
}
.cs-aging-bar { display: flex; border-radius: 8px; overflow: hidden; height: 10px; margin: .75rem 0 1rem; background: #f1f5f9; }
.cs-aging-segment { height: 100%; transition: width .4s; }
.cs-aging-buckets { display: flex; gap: .5rem; flex-wrap: wrap; }
.cs-aging-bucket {
    flex: 1; min-width: 80px; text-align: center;
    border-radius: 8px; padding: .6rem .5rem; border: 1px solid transparent;
}
.cs-aging-bucket .abl { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .7; }
.cs-aging-bucket .abv { font-size: .95rem; font-weight: 800; margin-top: .2rem; }

/* ── Mini tables ── */
.cs-mini-table { width: 100%; border-collapse: collapse; }
.cs-mini-table thead th {
    background: #f8fafc; font-size: .62rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .06em; color: #64748b; padding: .5rem .75rem;
    border-bottom: 1px solid #e2e8f0;
}
.cs-mini-table tbody td { padding: .55rem .75rem; font-size: .83rem; border-bottom: 1px solid #f1f5f9; color: #374151; }
.cs-mini-table tbody tr:last-child td { border-bottom: none; }
.cs-mini-table tbody tr:hover td { background: #f8faff; }

/* ── Note card ── */
.cs-note-card {
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 1.1rem 1.2rem; height: 100%;
}
.cs-note-label { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #d97706; margin-bottom: .6rem; display: flex; align-items: center; gap: .4rem; }
.cs-note-text { font-size: .88rem; color: #374151; white-space: pre-wrap; line-height: 1.6; }

/* ── Rx cards ── */
.cs-rx-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: .85rem 1rem; display: flex; gap: .85rem; align-items: flex-start;
    margin-bottom: .6rem; transition: box-shadow .15s;
}
.cs-rx-card:hover { box-shadow: 0 3px 12px rgba(15,23,42,.08); }
.cs-rx-thumb {
    width: 52px; height: 52px; border-radius: 8px; object-fit: cover;
    border: 1px solid #e2e8f0; flex-shrink: 0; cursor: pointer;
}
.cs-rx-thumb-placeholder {
    width: 52px; height: 52px; border-radius: 8px;
    background: #f1f5f9; display: flex; align-items: center; justify-content: center;
    color: #94a3b8; font-size: 1.2rem; flex-shrink: 0;
}
.cs-rx-title { font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: .2rem; }
.cs-rx-notes { font-size: .8rem; color: #64748b; line-height: 1.5; }
.cs-rx-date { font-size: .7rem; color: #94a3b8; margin-top: .3rem; }

/* ── Ledger ── */
.cs-ledger-wrap { border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.cs-ledger-head {
    background: linear-gradient(135deg, #f8faff, #eef3ff);
    border-bottom: 1px solid #e2e8f0; padding: .9rem 1.25rem;
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
}
.cs-ledger-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
.cs-ledger-table { width: 100%; border-collapse: collapse; }
.cs-ledger-table thead th {
    background: #f0f4fa; font-size: .62rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .06em; color: #374151; padding: .6rem .85rem;
    border-bottom: 2px solid #d1d5db; white-space: nowrap;
}
.cs-ledger-table tbody td { padding: .6rem .85rem; font-size: .84rem; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
.cs-ledger-table tbody tr:hover td { background: #f8faff; }
.cs-ledger-table tbody tr:nth-child(even) td { background: #fafbfd; }
.cs-ledger-table tbody tr:nth-child(even):hover td { background: #f0f5ff; }
.cs-ledger-table tfoot td { background: #f0f4fa; font-size: .8rem; font-weight: 800; color: #1e293b; padding: .6rem .85rem; border-top: 2px solid #d1d5db; }
.cs-type-badge { display: inline-block; font-size: .64rem; font-weight: 800; border-radius: 5px; padding: 2px 8px; white-space: nowrap; }
</style>

<div class="page-heading cs-page">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('customer.show') }}">Customers</a></li>
            <li class="breadcrumb-item active">{{ $customer->name }}</li>
        </ol>
    </nav>

    {{-- ① Profile Header ──────────────────────────────────── --}}
    <div class="cs-profile">
        <div class="cs-avatar">{{ $initials }}</div>
        <div class="cs-profile-info">
            <div class="cs-profile-name">{{ $customer->name }}</div>
            <div class="mb-2">
                @if($customer->is_member)
                    <span class="cs-badge-mem"><i class="bi bi-star-fill"></i> Member</span>
                @else
                    <span class="cs-badge-reg"><i class="bi bi-person"></i> Regular</span>
                @endif
                &nbsp;
                <span class="{{ ($customer->status ?? 'active') === 'active' ? 'cs-badge-active' : 'cs-badge-inactive' }}">
                    {{ ucfirst($customer->status ?? 'active') }}
                </span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:0">
                @if($customer->customer_code)
                    <span class="cs-chip"><i class="bi bi-hash"></i>{{ $customer->customer_code }}</span>
                @endif
                @if($customer->phone)
                    <span class="cs-chip"><i class="bi bi-telephone"></i>{{ $customer->phone }}</span>
                @endif
                @if($customer->email)
                    <span class="cs-chip"><i class="bi bi-envelope"></i>{{ $customer->email }}</span>
                @endif
                @if($customer->customerGroup)
                    <span class="cs-chip"><i class="bi bi-people"></i>{{ $customer->customerGroup->name }}</span>
                @endif
                @if($customer->is_member && $customer->membership_started_at)
                    <span class="cs-chip"><i class="bi bi-calendar-check"></i>Member since {{ DateFormatter::date($customer->membership_started_at) }}</span>
                @endif
                @if($customer->billing_address)
                    <span class="cs-chip"><i class="bi bi-geo-alt"></i>{{ $customer->billing_address }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-column gap-2">
            <a href="{{ route('customer.edit', $customer->id) }}" class="btn btn-sm btn-warning fw-bold px-3">
                <i class="bi bi-pencil-fill me-1"></i> Edit
            </a>
            <a href="{{ route('customer.show') }}" class="btn btn-sm fw-bold px-3" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2)">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- ② Financial Snapshot ─────────────────────────────── --}}
    <div class="cs-section-title"><i class="bi bi-bar-chart-fill"></i> Financial Overview</div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="cs-stat" style="border-left: 4px solid {{ $outstandingDue > 0 ? '#dc2626' : '#16a34a' }}">
                <div class="cs-stat-icon" style="background:{{ $outstandingDue > 0 ? '#fee2e2' : '#dcfce7' }};color:{{ $outstandingDue > 0 ? '#dc2626' : '#16a34a' }}">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <div class="cs-stat-label">Outstanding Due</div>
                <div class="cs-stat-value" style="color:{{ $outstandingDue > 0 ? '#dc2626' : '#16a34a' }}">{{ Currency::format($outstandingDue) }}</div>
                <div class="cs-stat-sub">{{ $outstandingDue > 0 ? 'Payment pending' : 'No dues' }}</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="cs-stat" style="border-left:4px solid #3b82f6">
                <div class="cs-stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-bag-fill"></i></div>
                <div class="cs-stat-label">Total Purchases</div>
                <div class="cs-stat-value">{{ Currency::format($salesGrandTotal) }}</div>
                <div class="cs-stat-sub">{{ $customer->salesOrders->count() }} order(s)</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="cs-stat" style="border-left:4px solid #059669">
                <div class="cs-stat-icon" style="background:#dcfce7;color:#059669"><i class="bi bi-cash-coin"></i></div>
                <div class="cs-stat-label">Collected</div>
                <div class="cs-stat-value" style="color:#059669">{{ Currency::format($collectionTotal) }}</div>
                <div class="cs-stat-sub">{{ $collectionEntries->count() }} payment(s)</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="cs-stat" style="border-left:4px solid #f59e0b">
                <div class="cs-stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-arrow-return-left"></i></div>
                <div class="cs-stat-label">Returns</div>
                <div class="cs-stat-value" style="color:#d97706">{{ Currency::format($returnTotal) }}</div>
                <div class="cs-stat-sub">{{ $customer->salesReturns->count() }} return(s)</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="cs-stat" style="border-left:4px solid #8b5cf6">
                <div class="cs-stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="bi bi-credit-card-2-back-fill"></i></div>
                <div class="cs-stat-label">Credit Limit</div>
                <div class="cs-stat-value" style="color:#7c3aed">{{ $customer->credit_limit !== null ? Currency::format($customer->credit_limit) : '∞' }}</div>
                @if($availableCredit !== null)
                    <div class="cs-stat-sub">Available: {{ Currency::format($availableCredit) }}</div>
                @else
                    <div class="cs-stat-sub">No limit set</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ③ Loyalty Row ─────────────────────────────────────── --}}
    <div class="cs-section-title"><i class="bi bi-star-fill"></i> Loyalty & Activity</div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="cs-stat" style="border-left:4px solid #3b82f6">
                <div class="cs-stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-coin"></i></div>
                <div class="cs-stat-label">Available Points</div>
                <div class="cs-stat-value" style="color:#1d4ed8">{{ number_format($customer->loyalty_points) }}</div>
                <div class="cs-stat-sub">≈ {{ Currency::format($customer->loyalty_points) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cs-stat" style="border-left:4px solid #10b981">
                <div class="cs-stat-icon" style="background:#d1fae5;color:#059669"><i class="bi bi-trophy-fill"></i></div>
                <div class="cs-stat-label">Lifetime Earned</div>
                <div class="cs-stat-value" style="color:#059669">{{ number_format($customer->lifetime_earned_points) }}</div>
                <div class="cs-stat-sub">pts total earned</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cs-stat" style="border-left:4px solid #f59e0b">
                <div class="cs-stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-gift-fill"></i></div>
                <div class="cs-stat-label">Redeemed</div>
                <div class="cs-stat-value" style="color:#d97706">{{ number_format($customer->lifetime_redeemed_points) }}</div>
                <div class="cs-stat-sub"><a href="{{ route('loyalty.adjust') }}" class="text-primary" style="font-size:.72rem">Adjust points</a></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cs-stat" style="border-left:4px solid #64748b">
                <div class="cs-stat-icon" style="background:#f1f5f9;color:#475569"><i class="bi bi-clock-history"></i></div>
                <div class="cs-stat-label">Last Activity</div>
                <div class="cs-stat-value" style="font-size:1rem;color:#0f172a">{{ $lastActivityAt ? DateFormatter::date($lastActivityAt) : '—' }}</div>
                <div class="cs-stat-sub">Opening bal: {{ Currency::format($customer->opening_balance) }}</div>
            </div>
        </div>
    </div>

    {{-- ④ Due Aging ───────────────────────────────────────── --}}
    @php
        $agingTotal = $agingBuckets['current'] + $agingBuckets['1_30'] + $agingBuckets['31_60'] + $agingBuckets['61_90'] + $agingBuckets['90_plus'];
        $pct = fn($v) => $agingTotal > 0 ? round(($v / $agingTotal) * 100, 1) : 0;
        $agingConfig = [
            ['label' => 'Current',  'key' => 'current', 'color' => '#3b82f6', 'bg' => '#eff6ff', 'text' => '#1d4ed8'],
            ['label' => '1–30 d',   'key' => '1_30',    'color' => '#22c55e', 'bg' => '#f0fdf4', 'text' => '#15803d'],
            ['label' => '31–60 d',  'key' => '31_60',   'color' => '#f59e0b', 'bg' => '#fffbeb', 'text' => '#d97706'],
            ['label' => '61–90 d',  'key' => '61_90',   'color' => '#f97316', 'bg' => '#fff7ed', 'text' => '#c2410c'],
            ['label' => '90+ d',    'key' => '90_plus', 'color' => '#ef4444', 'bg' => '#fef2f2', 'text' => '#dc2626'],
        ];
    @endphp
    <div class="cs-aging mb-4">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="cs-section-title mb-0" style="flex:1"><i class="bi bi-hourglass-split"></i> Due Aging</div>
            <span style="font-size:.78rem;font-weight:800;color:#dc2626">Total: {{ Currency::format($agingTotal) }}</span>
        </div>
        <div class="cs-aging-bar mt-2">
            @foreach($agingConfig as $ac)
                @if($agingBuckets[$ac['key']] > 0)
                    <div class="cs-aging-segment" style="width:{{ $pct($agingBuckets[$ac['key']]) }}%;background:{{ $ac['color'] }}" title="{{ $ac['label'] }}: {{ Currency::format($agingBuckets[$ac['key']]) }}"></div>
                @endif
            @endforeach
        </div>
        <div class="cs-aging-buckets">
            @foreach($agingConfig as $ac)
            <div class="cs-aging-bucket" style="background:{{ $ac['bg'] }};border-color:{{ $ac['color'] }}20">
                <div class="abl" style="color:{{ $ac['text'] }}">{{ $ac['label'] }}</div>
                <div class="abv" style="color:{{ $ac['text'] }}">{{ Currency::format($agingBuckets[$ac['key']]) }}</div>
            </div>
            @endforeach
        </div>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:.75rem">Calculated from each sales order date for balances still due.</div>
    </div>

    {{-- ⑤ Recent Activity (3 columns) ───────────────────── --}}
    <div class="cs-section-title"><i class="bi bi-activity"></i> Recent Activity</div>
    <div class="row g-3 mb-4">
        {{-- Recent Sales --}}
        <div class="col-xl-4">
            <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                <div style="background:#eff6ff;border-bottom:1px solid #bfdbfe;padding:.75rem 1rem;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-bag-fill" style="color:#1d4ed8;font-size:.95rem"></i>
                    <span style="font-weight:800;font-size:.88rem;color:#1e40af">Recent Sales</span>
                    <span class="ms-auto" style="font-size:.7rem;font-weight:700;color:#3b82f6">{{ $customer->salesOrders->count() }} total</span>
                </div>
                <div class="p-0">
                    @if($customer->salesOrders->isEmpty())
                        <div class="p-4 text-center text-muted small"><i class="bi bi-inbox d-block mb-1 fs-4"></i>No sales yet</div>
                    @else
                        <table class="cs-mini-table">
                            <thead><tr><th>Order</th><th>Date</th><th class="text-end">Due</th></tr></thead>
                            <tbody>
                                @foreach($customer->salesOrders->take(6) as $so)
                                <tr>
                                    <td>
                                        <span style="font-size:.75rem;font-weight:700;background:#dbeafe;color:#1e40af;border-radius:4px;padding:1px 6px">
                                            {{ $so->order_no ?: 'SO-'.$so->id }}
                                        </span>
                                        <div style="font-size:.7rem;color:#94a3b8;margin-top:1px">{{ $so->sales_channel_label }}</div>
                                    </td>
                                    <td style="font-size:.78rem;color:#64748b">{{ DateFormatter::date($so->order_date) }}</td>
                                    <td class="text-end">
                                        @if($so->due_total > 0)
                                            <span style="font-weight:800;color:#dc2626;font-size:.82rem">{{ Currency::format($so->due_total) }}</span>
                                        @else
                                            <span style="font-weight:700;color:#16a34a;font-size:.82rem">Paid</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($customer->salesOrders->count() > 6)
                            <div style="text-align:center;padding:.5rem;font-size:.72rem;color:#64748b;border-top:1px solid #f1f5f9">
                                +{{ $customer->salesOrders->count() - 6 }} more — see ledger below
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Collections --}}
        <div class="col-xl-4">
            <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                <div style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;padding:.75rem 1rem;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-cash-coin" style="color:#059669;font-size:.95rem"></i>
                    <span style="font-weight:800;font-size:.88rem;color:#065f46">Collections</span>
                    <span class="ms-auto" style="font-size:.7rem;font-weight:700;color:#16a34a">{{ $collectionEntries->count() }} total</span>
                </div>
                <div class="p-0">
                    @if($collectionEntries->isEmpty())
                        <div class="p-4 text-center text-muted small"><i class="bi bi-inbox d-block mb-1 fs-4"></i>No collections yet</div>
                    @else
                        <table class="cs-mini-table">
                            <thead><tr><th>Reference</th><th>Date</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                                @foreach($collectionEntries->take(6) as $pay)
                                <tr>
                                    <td style="font-size:.78rem;font-weight:600">{{ $pay->reference_no ?: 'PAY-'.$pay->id }}</td>
                                    <td style="font-size:.78rem;color:#64748b">{{ DateFormatter::date($pay->payment_date) }}</td>
                                    <td class="text-end"><span style="font-weight:800;color:#059669;font-size:.82rem">{{ Currency::format($pay->amount) }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($collectionEntries->count() > 6)
                            <div style="text-align:center;padding:.5rem;font-size:.72rem;color:#64748b;border-top:1px solid #f1f5f9">
                                +{{ $collectionEntries->count() - 6 }} more — see ledger below
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Returns --}}
        <div class="col-xl-4">
            <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                <div style="background:#fff7ed;border-bottom:1px solid #fed7aa;padding:.75rem 1rem;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-arrow-return-left" style="color:#c2410c;font-size:.95rem"></i>
                    <span style="font-weight:800;font-size:.88rem;color:#9a3412">Returns</span>
                    <span class="ms-auto" style="font-size:.7rem;font-weight:700;color:#f97316">{{ $customer->salesReturns->count() }} total</span>
                </div>
                <div class="p-0">
                    @if($customer->salesReturns->isEmpty())
                        <div class="p-4 text-center text-muted small"><i class="bi bi-inbox d-block mb-1 fs-4"></i>No returns yet</div>
                    @else
                        <table class="cs-mini-table">
                            <thead><tr><th>Return</th><th>Date</th><th class="text-end">Refund</th></tr></thead>
                            <tbody>
                                @foreach($customer->salesReturns->take(6) as $ret)
                                <tr>
                                    <td style="font-size:.78rem;font-weight:600">{{ $ret->return_no ?: 'SR-'.$ret->id }}</td>
                                    <td style="font-size:.78rem;color:#64748b">{{ DateFormatter::date($ret->return_date) }}</td>
                                    <td class="text-end"><span style="font-weight:800;color:#d97706;font-size:.82rem">{{ Currency::format($ret->refund_total) }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ⑥ Note & Prescriptions ──────────────────────────── --}}
    <div class="cs-section-title"><i class="bi bi-journal-medical"></i> Notes & Prescriptions</div>
    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="cs-note-card">
                <div class="cs-note-label"><i class="bi bi-sticky-fill"></i> Customer Note</div>
                @if($customer->note)
                    <div class="cs-note-text">{{ $customer->note }}</div>
                @else
                    <div style="font-size:.82rem;color:#d97706;opacity:.6">No note recorded.</div>
                @endif
            </div>
        </div>
        <div class="col-xl-8">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;height:100%">
                <div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:.75rem 1rem;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-file-medical-fill" style="color:#dc2626;font-size:.95rem"></i>
                    <span style="font-weight:800;font-size:.88rem;color:#991b1b">Prescriptions</span>
                    @if($customer->prescriptions->isNotEmpty())
                        <span style="background:#dc2626;color:#fff;font-size:.65rem;font-weight:800;border-radius:20px;padding:1px 8px;margin-left:4px">
                            {{ $customer->prescriptions->count() }}
                        </span>
                    @endif
                </div>
                <div style="padding:.85rem">
                    @if($customer->prescriptions->isEmpty())
                        <div class="text-center text-muted py-3 small"><i class="bi bi-file-medical d-block mb-1 fs-4"></i>No prescriptions recorded.</div>
                    @else
                        @foreach($customer->prescriptions as $rx)
                        <div class="cs-rx-card">
                            @if($rx->image_url)
                                <a href="{{ $rx->image_url }}" target="_blank">
                                    <img src="{{ $rx->image_url }}" alt="Rx" class="cs-rx-thumb">
                                </a>
                            @else
                                <div class="cs-rx-thumb-placeholder"><i class="bi bi-file-medical"></i></div>
                            @endif
                            <div style="flex:1;min-width:0">
                                <div class="cs-rx-title">{{ $rx->title ?: 'Prescription #'.$rx->id }}</div>
                                @if($rx->notes)
                                    <div class="cs-rx-notes">{{ $rx->notes }}</div>
                                @endif
                                <div class="cs-rx-date"><i class="bi bi-calendar3 me-1"></i>{{ DateFormatter::date($rx->created_at) }}</div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ⑦ Ledger Statement ───────────────────────────────── --}}
    <div class="cs-section-title"><i class="bi bi-journal-text"></i> Ledger Statement</div>
    <div class="cs-ledger-wrap mb-4">
        <div class="cs-ledger-head">
            <div class="cs-ledger-title">Full Transaction Ledger</div>
            <div style="font-size:.75rem;color:#64748b">{{ count($ledgerEntries) }} entries</div>
        </div>
        <div style="overflow-x:auto">
            <table class="cs-ledger-table">
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
                    @php
                        $typeMap = [
                            'Opening Balance' => ['#eff6ff','#1d4ed8'],
                            'Sale'            => ['#dbeafe','#1e40af'],
                            'Collection'      => ['#dcfce7','#15803d'],
                            'Return'          => ['#fef3c7','#92400e'],
                            'Adjustment'      => ['#f3e8ff','#6d28d9'],
                        ];
                        [$tbg,$tc] = $typeMap[$entry['type']] ?? ['#f1f5f9','#475569'];
                    @endphp
                    <tr>
                        <td style="font-size:.82rem;color:#475569;white-space:nowrap">{{ DateFormatter::date($entry['date']) }}</td>
                        <td style="font-size:.82rem;font-weight:600">{{ $entry['reference'] }}</td>
                        <td>
                            <span class="cs-type-badge" style="background:{{ $tbg }};color:{{ $tc }}">{{ $entry['type'] }}</span>
                        </td>
                        <td style="font-size:.8rem;color:#64748b;max-width:180px">{{ $entry['note'] ?: '—' }}</td>
                        <td class="text-end">
                            @if($entry['debit'] > 0)
                                <span style="font-weight:800;color:#dc2626">{{ Currency::format($entry['debit']) }}</span>
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($entry['credit'] > 0)
                                <span style="font-weight:800;color:#16a34a">{{ Currency::format($entry['credit']) }}</span>
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span style="font-weight:800;color:{{ $entry['balance'] > 0 ? '#dc2626' : ($entry['balance'] < 0 ? '#059669' : '#475569') }}">
                                {{ Currency::format(abs($entry['balance'])) }}
                                @if($entry['balance'] != 0)
                                    <span style="font-size:.62rem;font-weight:600">&nbsp;{{ $entry['balance'] > 0 ? 'Dr' : 'Cr' }}</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x d-block mb-1 fs-4"></i>No ledger activity yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($ledgerEntries) > 0)
                @php $lastEntry = collect($ledgerEntries)->last(); @endphp
                <tfoot>
                    <tr>
                        <td colspan="4" style="font-size:.78rem">Closing Balance</td>
                        <td class="text-end" style="color:#dc2626">
                            {{ Currency::format(collect($ledgerEntries)->sum('debit')) }}
                        </td>
                        <td class="text-end" style="color:#16a34a">
                            {{ Currency::format(collect($ledgerEntries)->sum('credit')) }}
                        </td>
                        <td class="text-end" style="color:{{ $lastEntry['balance'] > 0 ? '#dc2626' : '#059669' }}">
                            {{ Currency::format(abs($lastEntry['balance'])) }}
                            @if($lastEntry['balance'] != 0)
                                <span style="font-size:.65rem;font-weight:600">&nbsp;{{ $lastEntry['balance'] > 0 ? 'Dr' : 'Cr' }}</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
