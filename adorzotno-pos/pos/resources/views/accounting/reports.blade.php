@extends('layouts.main')
@section('main.content')
<style>
/* ── Financial Overview Cards ─────────────── */
.fin-card {
    border-radius: 12px;
    padding: .9rem 1.1rem;
    display: flex;
    align-items: center;
    gap: .9rem;
    border: 1px solid transparent;
}
.fin-card .fi { width: 42px; height: 42px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
.fin-card .fv { font-size: 1.1rem; font-weight: 800; line-height: 1.1; }
.fin-card .fl { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-top: .15rem; opacity: .8; }
.fin-card .fb { font-size: .58rem; font-weight: 600; color: #94a3b8; margin-top: .08rem; }

.fc-cash  { background:#f0fdf4; border-color:#bbf7d0; } .fc-cash  .fi { background:#dcfce7; color:#15803d; } .fc-cash  .fv { color:#15803d; } .fc-cash  .fl { color:#15803d; }
.fc-bank  { background:#eff6ff; border-color:#bfdbfe; } .fc-bank  .fi { background:#dbeafe; color:#1d4ed8; } .fc-bank  .fv { color:#1d4ed8; } .fc-bank  .fl { color:#1d4ed8; }
.fc-recv  { background:#fff7ed; border-color:#fed7aa; } .fc-recv  .fi { background:#ffedd5; color:#c2410c; } .fc-recv  .fv { color:#c2410c; } .fc-recv  .fl { color:#c2410c; }
.fc-pay   { background:#fdf4ff; border-color:#e9d5ff; } .fc-pay   .fi { background:#f3e8ff; color:#7e22ce; } .fc-pay   .fv { color:#7e22ce; } .fc-pay   .fl { color:#7e22ce; }
.fc-inc   { background:#f0fdf4; border-color:#bbf7d0; } .fc-inc   .fi { background:#dcfce7; color:#15803d; } .fc-inc   .fv { color:#15803d; font-size:.95rem; } .fc-inc   .fl { color:#15803d; }
.fc-exp   { background:#fef2f2; border-color:#fecaca; } .fc-exp   .fi { background:#fee2e2; color:#dc2626; } .fc-exp   .fv { color:#dc2626; font-size:.95rem; } .fc-exp   .fl { color:#dc2626; }
.fc-prof  { background:#eff6ff; border-color:#bfdbfe; } .fc-prof  .fi { background:#dbeafe; color:#1d4ed8; } .fc-prof  .fv { color:#1d4ed8; font-size:.95rem; } .fc-prof  .fl { color:#1d4ed8; }
.fc-prof.loss  { background:#fef2f2; border-color:#fecaca; } .fc-prof.loss  .fi { background:#fee2e2; color:#dc2626; } .fc-prof.loss  .fv { color:#dc2626; } .fc-prof.loss  .fl { color:#dc2626; }

/* ── Report type tabs ─────────────────────── */
.rpt-type-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.rpt-type-btn {
    padding: .38rem .85rem;
    border-radius: 20px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    font-size: .78rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
}
.rpt-type-btn:hover { background: #f0f4ff; border-color: #6366f1; color: #4f46e5; }
.rpt-type-btn.active { background: #4f46e5; border-color: #4f46e5; color: #fff; }

/* ── Quick date presets ───────────────────── */
.date-preset-bar {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    align-items: center;
}
.dp-btn {
    padding: .28rem .7rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: .73rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
}
.dp-btn:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }

/* ── Report results ───────────────────────── */
.rpt-main-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(15,23,42,.06);
    overflow: hidden;
}
.rpt-filter-bar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.25rem;
}
.rpt-filter-label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #475569; display: block; margin-bottom: .25rem; }

/* P&L */
.pl-section { margin-bottom: 1.5rem; }
.pl-section-title { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; padding: .5rem .75rem; border-radius: 6px; margin-bottom: .75rem; }
.pl-income-title  { background: #dcfce7; color: #15803d; }
.pl-expense-title { background: #fee2e2; color: #dc2626; }
.pl-row { display: flex; justify-content: space-between; padding: .45rem .75rem; border-radius: 6px; font-size: .875rem; }
.pl-row:nth-child(odd) { background: #f8fafc; }
.pl-row-name  { color: #374151; font-weight: 500; }
.pl-row-amt   { font-weight: 700; }
.pl-total-row { display: flex; justify-content: space-between; padding: .6rem .75rem; border-radius: 8px; font-weight: 800; font-size: .95rem; margin-top: .5rem; }
.pl-total-income  { background: #dcfce7; color: #15803d; }
.pl-total-expense { background: #fee2e2; color: #dc2626; }
.pl-net-row { padding: .9rem 1.1rem; border-radius: 10px; display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 900; margin-top: 1rem; }
.pl-profit { background: linear-gradient(135deg,#4ade80,#22c55e); color: #14532d; }
.pl-loss   { background: linear-gradient(135deg,#f87171,#ef4444); color: #fff; }

/* Ledger table */
.ledger-tbl thead th {
    background: #f0f4fa;
    font-size: .66rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    border-bottom: 2px solid #d1d5db;
    padding: .65rem .75rem;
    white-space: nowrap;
    color: #374151;
}
.ledger-tbl tbody td {
    padding: .55rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: .84rem;
    vertical-align: middle;
}
.ledger-tbl tbody tr:hover { background: #f8faff; }
.ledger-tbl tfoot th { background: #f0f4fa; font-weight: 800; font-size: .82rem; border-top: 2px solid #d1d5db; padding: .6rem .75rem; }
.debit-cell  { color: #dc2626; font-weight: 700; }
.credit-cell { color: #15803d; font-weight: 700; }
.balance-cell { font-weight: 800; color: #1d4ed8; }
.balance-cell.negative { color: #dc2626; }

.ref-badge { font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; white-space: nowrap; }
.ref-sale        { background: #dcfce7; color: #15803d; }
.ref-purchase    { background: #dbeafe; color: #1d4ed8; }
.ref-expense     { background: #fee2e2; color: #dc2626; }
.ref-manual      { background: #e0e7ff; color: #3730a3; }
.ref-transfer    { background: #fef3c7; color: #92400e; }
.ref-return      { background: #ede9fe; color: #5b21b6; }
.ref-default     { background: #f1f5f9; color: #475569; }

/* Due table */
.due-tbl thead th { background: #f0f4fa; font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; border-bottom: 2px solid #d1d5db; padding: .65rem .75rem; }
.due-tbl tbody td { padding: .55rem .75rem; border-bottom: 1px solid #f1f5f9; font-size: .875rem; }
.due-tbl tbody tr:hover { background: #f8faff; }

.rpt-scroll { overflow-x: auto; }
.rpt-scroll::-webkit-scrollbar { height: 5px; }
.rpt-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

/* Trial Balance */
.tb-table thead th { background:#f0f4fa; font-size:.67rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; border-bottom:2px solid #d1d5db; padding:.6rem .75rem; color:#374151; }
.tb-table tbody td { padding:.5rem .75rem; border-bottom:1px solid #f1f5f9; font-size:.875rem; }
.tb-table tbody tr:hover { background:#f8faff; }
.tb-table tfoot th { background:#f0f4fa; font-weight:800; border-top:2px solid #d1d5db; padding:.6rem .75rem; }
.tb-type-badge { font-size:.62rem; font-weight:700; padding:1px 7px; border-radius:5px; text-transform:capitalize; }
.tb-balanced-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .9rem; border-radius:8px; font-size:.78rem; font-weight:700; }

/* Balance Sheet */
.bs-section { margin-bottom:1.5rem; }
.bs-section-title { font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; padding:.5rem .85rem; border-radius:7px; margin-bottom:.6rem; }
.bs-asset-title   { background:#dbeafe; color:#1d4ed8; }
.bs-liab-title    { background:#fee2e2; color:#dc2626; }
.bs-equity-title  { background:#d1fae5; color:#065f46; }
.bs-row { display:flex; justify-content:space-between; align-items:center; padding:.4rem .85rem; border-radius:6px; font-size:.875rem; }
.bs-row:nth-child(odd) { background:#f8fafc; }
.bs-row-name { color:#374151; font-weight:500; }
.bs-row-type { font-size:.65rem; color:#94a3b8; margin-left:.4rem; }
.bs-row-amt  { font-weight:700; color:#1e293b; white-space:nowrap; }
.bs-total-row { display:flex; justify-content:space-between; align-items:center; padding:.6rem .85rem; border-radius:8px; font-weight:800; font-size:.95rem; margin-top:.5rem; }
.bs-total-asset { background:#dbeafe; color:#1d4ed8; }
.bs-total-liab  { background:#fee2e2; color:#dc2626; }
.bs-total-equity{ background:#d1fae5; color:#065f46; }
.bs-equation { padding:1rem; border-radius:10px; margin-top:1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
.bs-equation.balanced   { background:linear-gradient(135deg,#4ade80,#22c55e); color:#14532d; }
.bs-equation.unbalanced { background:linear-gradient(135deg,#f87171,#ef4444); color:#fff; }
.bs-eq-item { text-align:center; }
.bs-eq-label { font-size:.7rem; font-weight:700; opacity:.8; display:block; margin-bottom:.15rem; }
.bs-eq-val   { font-size:1.1rem; font-weight:900; }
.bs-eq-sign  { font-size:1.5rem; font-weight:900; opacity:.7; }

/* Opening balance row */
.opening-balance-row td { background:#fffbeb; font-weight:700; font-size:.82rem; color:#92400e; border-bottom:2px solid #fcd34d; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Accounting &amp; Finance</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Financial Reports</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- ── Financial Health Overview ──────────────────── --}}
    @php $mn = now()->format('F Y'); @endphp
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-lg">
            <div class="fin-card fc-cash">
                <div class="fi"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format($overview['cash'],2) }}</div>
                    <div class="fl">Cash Balance</div>
                    <div class="fb"><i class="bi bi-dot"></i>Live</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="fin-card fc-bank">
                <div class="fi"><i class="bi bi-bank"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format($overview['bank'],2) }}</div>
                    <div class="fl">Bank Balance</div>
                    <div class="fb"><i class="bi bi-dot"></i>Live</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="fin-card fc-recv">
                <div class="fi"><i class="bi bi-arrow-down-circle"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format($overview['receivable'],2) }}</div>
                    <div class="fl">Receivable</div>
                    <div class="fb"><i class="bi bi-dot"></i>Live</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="fin-card fc-pay">
                <div class="fi"><i class="bi bi-arrow-up-circle"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format($overview['payable'],2) }}</div>
                    <div class="fl">Payable</div>
                    <div class="fb"><i class="bi bi-dot"></i>Live</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="fin-card fc-inc">
                <div class="fi"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format($overview['month_income'],2) }}</div>
                    <div class="fl">Income</div>
                    <div class="fb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $mn }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="fin-card fc-exp">
                <div class="fi"><i class="bi bi-graph-down-arrow"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format($overview['month_expense'],2) }}</div>
                    <div class="fl">Expenses</div>
                    <div class="fb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $mn }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg">
            @php $isProfit = $overview['month_profit'] >= 0; @endphp
            <div class="fin-card fc-prof {{ $isProfit ? '' : 'loss' }}">
                <div class="fi"><i class="bi bi-{{ $isProfit ? 'trophy' : 'exclamation-triangle' }}"></i></div>
                <div>
                    <div class="fv">৳ {{ number_format(abs($overview['month_profit']),2) }}</div>
                    <div class="fl">Net {{ $isProfit ? 'Profit' : 'Loss' }}</div>
                    <div class="fb"><i class="bi bi-calendar3 me-1"></i>This Month · {{ $mn }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Report Card ───────────────────────────── --}}
    <section class="section">
        <div class="rpt-main-card">

            {{-- Report type tabs --}}
            <div class="rpt-type-tabs">
                @php
                $reportTypes = [
                    'profit_loss'    => ['icon' => 'bi-bar-chart-line',       'label' => 'Profit & Loss'],
                    'trial_balance'  => ['icon' => 'bi-list-columns',          'label' => 'Trial Balance'],
                    'balance_sheet'  => ['icon' => 'bi-layout-split',          'label' => 'Balance Sheet'],
                    'general_ledger' => ['icon' => 'bi-journal-text',          'label' => 'General Ledger'],
                    'account_ledger' => ['icon' => 'bi-person-lines-fill',     'label' => 'Account Ledger'],
                    'cash_book'      => ['icon' => 'bi-cash-coin',             'label' => 'Cash Book'],
                    'bank_book'      => ['icon' => 'bi-bank',                  'label' => 'Bank Book'],
                    'receivable'     => ['icon' => 'bi-arrow-down-circle',     'label' => 'Receivable'],
                    'payable'        => ['icon' => 'bi-arrow-up-circle',       'label' => 'Payable'],
                ];
                @endphp
                @foreach($reportTypes as $key => $rt)
                <a href="?report={{ $key }}&startDate={{ request('startDate') }}&endDate={{ request('endDate') }}&branch_id={{ request('branch_id') }}&account_id={{ request('account_id') }}"
                   class="rpt-type-btn {{ $report === $key ? 'active' : '' }}">
                    <i class="bi {{ $rt['icon'] }}"></i> {{ $rt['label'] }}
                </a>
                @endforeach
            </div>

            {{-- Filter bar --}}
            <form method="get" action="{{ route('accounting.reports') }}" id="reportForm">
                <input type="hidden" name="report" value="{{ $report }}">
                <div class="rpt-filter-bar">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-auto">
                            <span class="rpt-filter-label">Quick Range</span>
                            <div class="date-preset-bar">
                                <button type="button" class="dp-btn" data-preset="today">Today</button>
                                <button type="button" class="dp-btn" data-preset="week">This Week</button>
                                <button type="button" class="dp-btn" data-preset="month">This Month</button>
                                <button type="button" class="dp-btn" data-preset="last_month">Last Month</button>
                                <button type="button" class="dp-btn" data-preset="quarter">This Quarter</button>
                                <button type="button" class="dp-btn" data-preset="year">This Year</button>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <span class="rpt-filter-label">Start Date</span>
                            <input type="date" name="startDate" id="startDate" value="{{ request('startDate') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 col-6">
                            <span class="rpt-filter-label">End Date</span>
                            <input type="date" name="endDate" id="endDate" value="{{ request('endDate') }}" class="form-control form-control-sm">
                        </div>
                        @if(!empty($branches) && $branches->isNotEmpty())
                        <div class="col-md-2 col-6">
                            <span class="rpt-filter-label">Branch</span>
                            <select name="branch_id" class="form-select form-select-sm">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if(in_array($report, ['account_ledger', 'general_ledger']))
                        <div class="col-md-2 col-6">
                            <span class="rpt-filter-label">Account</span>
                            <select name="account_id" class="form-select form-select-sm">
                                <option value="">All Accounts</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->account_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-auto col-12 d-flex gap-2 align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="bi bi-funnel-fill me-1"></i>Generate
                            </button>
                            <button type="submit" formaction="{{ route('accounting.reports.excel') }}" class="btn btn-success btn-sm px-3">
                                <i class="bi bi-file-earmark-excel me-1"></i>Excel
                            </button>
                            <a href="{{ route('accounting.reports') }}?report={{ $report }}" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            {{-- ── Report Content ─────────────────────── --}}
            <div class="p-4">

                @if($report === 'trial_balance')
                {{-- ─── TRIAL BALANCE ──────────────────── --}}
                @php
                    $tbBalanced = $data['balanced'];
                    $tbRows     = $data['rows'];
                @endphp
                <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">Trial Balance</h6>
                        <div class="text-muted small">All accounts with net debit / credit balances.</div>
                    </div>
                    <span class="tb-balanced-badge {{ $tbBalanced ? 'bg-success text-white' : 'bg-danger text-white' }}">
                        <i class="bi bi-{{ $tbBalanced ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
                        {{ $tbBalanced ? 'Balanced ✓' : 'NOT Balanced — Check Journals' }}
                    </span>
                </div>
                <div class="rpt-scroll">
                    <table class="table tb-table align-middle mb-0" style="min-width:600px">
                        <thead>
                            <tr>
                                <th style="width:80px">Code</th>
                                <th>Account Name</th>
                                <th style="width:110px">Type</th>
                                <th class="text-end" style="width:140px">Debit (Dr)</th>
                                <th class="text-end" style="width:140px">Credit (Cr)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $typeColors = ['cash'=>'#dcfce7:#15803d','bank'=>'#dbeafe:#1d4ed8','mobile_banking'=>'#dbeafe:#1d4ed8','asset'=>'#e0e7ff:#3730a3','receivable'=>'#fff7ed:#c2410c','payable'=>'#fee2e2:#dc2626','expense'=>'#fee2e2:#dc2626','income'=>'#dcfce7:#15803d','equity'=>'#f3e8ff:#7e22ce','liability'=>'#fef3c7:#92400e'];
                            @endphp
                            @forelse($tbRows as $row)
                            @php [$bg,$clr] = explode(':',$typeColors[$row['type']] ?? '#f1f5f9:#64748b'); @endphp
                            <tr>
                                <td style="font-size:.75rem;color:#94a3b8;font-weight:600">{{ $row['code'] }}</td>
                                <td style="font-weight:600;color:#1e293b">{{ $row['name'] }}</td>
                                <td>
                                    <span class="tb-type-badge" style="background:{{ $bg }};color:{{ $clr }}">{{ $row['type'] }}</span>
                                </td>
                                <td class="text-end">
                                    @if($row['debit'] > 0)
                                    <span class="debit-cell">{{ number_format($row['debit'],2) }}</span>
                                    @else<span class="text-muted">—</span>@endif
                                </td>
                                <td class="text-end">
                                    @if($row['credit'] > 0)
                                    <span class="credit-cell">{{ number_format($row['credit'],2) }}</span>
                                    @else<span class="text-muted">—</span>@endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No account activity found.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end" style="color:#94a3b8;font-size:.7rem">TOTALS</th>
                                <th class="text-end debit-cell">{{ number_format($data['total_debit'],2) }}</th>
                                <th class="text-end credit-cell">{{ number_format($data['total_credit'],2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @elseif($report === 'balance_sheet')
                {{-- ─── BALANCE SHEET ───────────────────── --}}
                @php
                    $bsBalanced = $data['balanced'];
                @endphp
                <div class="row">
                    {{-- Assets --}}
                    <div class="col-md-6">
                        <div class="bs-section">
                            <div class="bs-section-title bs-asset-title">
                                <i class="bi bi-building me-2"></i>Assets
                            </div>
                            @forelse($data['assets'] as $item)
                            <div class="bs-row">
                                <span class="bs-row-name">{{ $item['name'] }}
                                    <span class="bs-row-type">({{ $item['type'] }})</span>
                                </span>
                                <span class="bs-row-amt">৳ {{ number_format($item['balance'],2) }}</span>
                            </div>
                            @empty
                            <div class="text-muted small px-3">No asset accounts found.</div>
                            @endforelse
                            <div class="bs-total-row bs-total-asset">
                                <span>Total Assets</span>
                                <span>৳ {{ number_format($data['total_assets'],2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Liabilities + Equity --}}
                    <div class="col-md-6">
                        <div class="bs-section">
                            <div class="bs-section-title bs-liab-title">
                                <i class="bi bi-arrow-up-circle me-2"></i>Liabilities
                            </div>
                            @forelse($data['liabilities'] as $item)
                            <div class="bs-row">
                                <span class="bs-row-name">{{ $item['name'] }}
                                    <span class="bs-row-type">({{ $item['type'] }})</span>
                                </span>
                                <span class="bs-row-amt">৳ {{ number_format($item['balance'],2) }}</span>
                            </div>
                            @empty
                            <div class="text-muted small px-3">No liability accounts found.</div>
                            @endforelse
                            <div class="bs-total-row bs-total-liab">
                                <span>Total Liabilities</span>
                                <span>৳ {{ number_format($data['total_liabilities'],2) }}</span>
                            </div>
                        </div>

                        <div class="bs-section">
                            <div class="bs-section-title bs-equity-title">
                                <i class="bi bi-person-check me-2"></i>Equity
                            </div>
                            @foreach($data['equity'] as $item)
                            <div class="bs-row">
                                <span class="bs-row-name">{{ $item['name'] }}</span>
                                <span class="bs-row-amt">৳ {{ number_format($item['balance'],2) }}</span>
                            </div>
                            @endforeach
                            <div class="bs-row">
                                <span class="bs-row-name">Retained Earnings (Net Profit)</span>
                                <span class="bs-row-amt {{ $data['retained_earnings'] >= 0 ? 'credit-cell' : 'debit-cell' }}">
                                    ৳ {{ number_format(abs($data['retained_earnings']),2) }}
                                    {{ $data['retained_earnings'] < 0 ? '(Loss)' : '' }}
                                </span>
                            </div>
                            <div class="bs-total-row bs-total-equity">
                                <span>Total Equity</span>
                                <span>৳ {{ number_format($data['total_equity'],2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Balance sheet equation check --}}
                <div class="bs-equation {{ $bsBalanced ? 'balanced' : 'unbalanced' }}">
                    <div class="bs-eq-item">
                        <span class="bs-eq-label">Total Assets</span>
                        <span class="bs-eq-val">৳ {{ number_format($data['total_assets'],2) }}</span>
                    </div>
                    <span class="bs-eq-sign">=</span>
                    <div class="bs-eq-item">
                        <span class="bs-eq-label">Total Liabilities</span>
                        <span class="bs-eq-val">৳ {{ number_format($data['total_liabilities'],2) }}</span>
                    </div>
                    <span class="bs-eq-sign">+</span>
                    <div class="bs-eq-item">
                        <span class="bs-eq-label">Total Equity</span>
                        <span class="bs-eq-val">৳ {{ number_format($data['total_equity'],2) }}</span>
                    </div>
                    <div class="bs-eq-item">
                        <span class="bs-eq-label">Status</span>
                        <span class="bs-eq-val">{{ $bsBalanced ? '✓ Balanced' : '✗ Not Balanced' }}</span>
                    </div>
                </div>

                @elseif($report === 'profit_loss')
                {{-- ─── PROFIT & LOSS ─────────────────── --}}
                @php
                    $income  = $data['income'];
                    $expense = $data['expense'];
                    $net     = $data['net_profit'];
                    $totalForPct = max($income, 1);
                @endphp
                <div class="row">
                    <div class="col-md-6">
                        <div class="pl-section">
                            <div class="pl-section-title pl-income-title">
                                <i class="bi bi-graph-up-arrow me-2"></i>Income
                            </div>
                            @forelse($plDetail['income'] ?? [] as $item)
                            <div class="pl-row">
                                <span class="pl-row-name">{{ $item['name'] }}</span>
                                <span class="pl-row-amt credit-cell">৳ {{ number_format($item['amount'],2) }}</span>
                            </div>
                            @empty
                            <div class="text-muted small px-2">No income entries for this period.</div>
                            @endforelse
                            <div class="pl-total-row pl-total-income">
                                <span>Total Income</span>
                                <span>৳ {{ number_format($income,2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="pl-section">
                            <div class="pl-section-title pl-expense-title">
                                <i class="bi bi-graph-down-arrow me-2"></i>Expenses
                            </div>
                            @forelse($plDetail['expense'] ?? [] as $item)
                            <div class="pl-row">
                                <span class="pl-row-name">{{ $item['name'] }}</span>
                                <span class="pl-row-amt debit-cell">৳ {{ number_format($item['amount'],2) }}</span>
                            </div>
                            @empty
                            <div class="text-muted small px-2">No expense entries for this period.</div>
                            @endforelse
                            <div class="pl-total-row pl-total-expense">
                                <span>Total Expenses</span>
                                <span>৳ {{ number_format($expense,2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pl-net-row {{ $net >= 0 ? 'pl-profit' : 'pl-loss' }}">
                    <span><i class="bi bi-{{ $net >= 0 ? 'trophy-fill' : 'exclamation-triangle-fill' }} me-2"></i>Net {{ $net >= 0 ? 'Profit' : 'Loss' }}</span>
                    <span>৳ {{ number_format(abs($net),2) }}</span>
                </div>

                @elseif(in_array($report, ['receivable', 'payable']))
                {{-- ─── RECEIVABLE / PAYABLE ───────────── --}}
                <div class="rpt-scroll">
                    <table class="table due-tbl align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th class="text-end">Total Due</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @forelse($data['rows'] as $row)
                            @php $due = (float)($row->due_total ?? $row->current_due ?? 0); @endphp
                            <tr>
                                <td class="text-muted small">{{ $i++ }}</td>
                                <td style="font-weight:700">{{ $row->name }}</td>
                                <td style="font-size:.82rem;color:#64748b">{{ $row->phone ?? '—' }}</td>
                                <td class="text-end">
                                    @if($due > 0)
                                    <span style="font-weight:800;color:#dc2626">৳ {{ number_format($due,2) }}</span>
                                    @else
                                    <span style="font-weight:600;color:#15803d">৳ 0.00</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($due > 0)
                                    <span style="background:#fee2e2;color:#991b1b;font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:5px">Outstanding</span>
                                    @else
                                    <span style="background:#dcfce7;color:#15803d;font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:5px">Cleared</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @else
                {{-- ─── LEDGER / CASH BOOK / BANK BOOK ── --}}
                @php
                    $runBalance    = 0;
                    $lines         = $data['lines'];
                    $showBalance   = $data['show_balance'] ?? false;
                    // For credit-normal accounts (income, payable, equity, liability):
                    //   credit INCREASES balance, debit DECREASES it
                    // For debit-normal accounts (asset, expense, cash, bank):
                    //   debit INCREASES balance, credit DECREASES it
                    $isCreditNormal = $data['is_credit_normal'] ?? false;
                @endphp
                <div class="rpt-scroll">
                    <table class="table ledger-tbl align-middle mb-0" style="min-width:{{ $showBalance ? '960px' : '830px' }}">
                        <thead>
                            <tr>
                                <th style="width:100px">Date</th>
                                <th>Account</th>
                                <th>Branch</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end" style="width:110px">Debit (Dr)</th>
                                <th class="text-end" style="width:110px">Credit (Cr)</th>
                                @if($showBalance)
                                <th class="text-end" style="width:120px">Balance</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $openingBal = $data['opening_balance'] ?? 0;
                                $runBalance = $openingBal;
                            @endphp
                            {{-- Opening Balance row --}}
                            @if($showBalance && request('startDate') && $openingBal != 0)
                            <tr class="opening-balance-row">
                                <td>{{ request('startDate') }}</td>
                                <td colspan="{{ $showBalance ? 5 : 4 }}">
                                    <i class="bi bi-arrow-right-circle me-1"></i>Opening Balance (brought forward)
                                </td>
                                <td class="text-end" colspan="0"></td>
                                <td class="text-end" colspan="0"></td>
                                @if($showBalance)
                                <td class="text-end">
                                    <span style="font-weight:800;color:#92400e">
                                        {{ $openingBal < 0 ? '(' . number_format(abs($openingBal),2) . ')' : number_format($openingBal,2) }}
                                    </span>
                                </td>
                                @endif
                            </tr>
                            @endif
                            @forelse($lines as $line)
                            @php
                                // Correct running balance based on account type
                                if ($isCreditNormal) {
                                    $runBalance += (float)$line->credit - (float)$line->debit;
                                } else {
                                    $runBalance += (float)$line->debit - (float)$line->credit;
                                }
                                $refType = $line->journal?->reference_type ?? '';
                                $refClass = match(true) {
                                    str_contains($refType,'sale') && !str_contains($refType,'return') => 'ref-sale',
                                    str_contains($refType,'purchase') => 'ref-purchase',
                                    str_contains($refType,'expense') => 'ref-expense',
                                    $refType === 'manual' => 'ref-manual',
                                    str_contains($refType,'transfer') => 'ref-transfer',
                                    str_contains($refType,'return') => 'ref-return',
                                    default => 'ref-default',
                                };
                                $refLabel = ucwords(str_replace('_',' ',$refType));
                            @endphp
                            <tr>
                                <td style="font-size:.8rem;font-weight:700;white-space:nowrap">
                                    {{ \App\Support\DateFormatter::date($line->journal?->journal_date) }}
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:.83rem;color:#1e293b">{{ $line->account?->account_name ?? 'N/A' }}</div>
                                    <div style="font-size:.68rem;color:#94a3b8;text-transform:capitalize">{{ $line->account?->account_type }}</div>
                                </td>
                                <td style="font-size:.8rem;color:#64748b">{{ $line->branch?->name ?? 'Global' }}</td>
                                <td>
                                    <span class="ref-badge {{ $refClass }}">{{ $refLabel }}</span>
                                    @if($line->journal?->reference_id)
                                    <span style="font-size:.72rem;color:#94a3b8"> #{{ $line->journal->reference_id }}</span>
                                    @endif
                                </td>
                                <td style="font-size:.8rem;color:#475569;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $line->description ?? '—' }}</td>
                                <td class="text-end">
                                    @if($line->debit > 0)
                                    <span class="debit-cell">{{ number_format($line->debit,2) }}</span>
                                    @else<span class="text-muted">—</span>@endif
                                </td>
                                <td class="text-end">
                                    @if($line->credit > 0)
                                    <span class="credit-cell">{{ number_format($line->credit,2) }}</span>
                                    @else<span class="text-muted">—</span>@endif
                                </td>
                                @if($showBalance)
                                <td class="text-end">
                                    <span class="balance-cell {{ $runBalance < 0 ? 'negative' : '' }}">
                                        {{ $runBalance < 0 ? '(' . number_format(abs($runBalance),2) . ')' : number_format($runBalance,2) }}
                                    </span>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $showBalance ? 8 : 7 }}" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-text fs-3 d-block mb-2 opacity-25"></i>
                                    No entries found for this period.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($lines->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end text-muted" style="font-size:.7rem">TOTALS</th>
                                <th class="text-end debit-cell">{{ number_format($data['debit'],2) }}</th>
                                <th class="text-end credit-cell">{{ number_format($data['credit'],2) }}</th>
                                @if($showBalance)
                                <th class="text-end balance-cell {{ $runBalance < 0 ? 'negative' : '' }}">
                                    <span style="font-size:.68rem;color:#94a3b8;display:block">Closing</span>
                                    {{ $runBalance < 0 ? '(' . number_format(abs($runBalance),2) . ')' : number_format($runBalance,2) }}
                                </th>
                                @endif
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                @endif

            </div>{{-- end .p-4 --}}
        </div>{{-- end .rpt-main-card --}}
    </section>
</div>
@endsection

@section('footer.js')
<script>
// Quick date preset buttons
document.querySelectorAll('.dp-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var preset = this.dataset.preset;
        var now    = new Date();
        var s = '', e = '';
        var pad = function(n){ return n < 10 ? '0'+n : n; };
        var fmt = function(d){ return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate()); };

        if (preset === 'today') {
            s = e = fmt(now);
        } else if (preset === 'week') {
            var day = now.getDay(), diff = now.getDate() - day + (day === 0 ? -6 : 1);
            var mon = new Date(now.setDate(diff));
            var sun = new Date(now); sun.setDate(mon.getDate() + 6);
            s = fmt(mon); e = fmt(sun);
        } else if (preset === 'month') {
            s = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
            e = fmt(new Date(now.getFullYear(), now.getMonth()+1, 0));
        } else if (preset === 'last_month') {
            s = fmt(new Date(now.getFullYear(), now.getMonth()-1, 1));
            e = fmt(new Date(now.getFullYear(), now.getMonth(), 0));
        } else if (preset === 'quarter') {
            var q = Math.floor(now.getMonth() / 3);
            s = fmt(new Date(now.getFullYear(), q*3, 1));
            e = fmt(new Date(now.getFullYear(), q*3+3, 0));
        } else if (preset === 'year') {
            s = now.getFullYear() + '-01-01';
            e = now.getFullYear() + '-12-31';
        }

        document.getElementById('startDate').value = s;
        document.getElementById('endDate').value   = e;
    });
});
</script>
@endsection
