@extends('layouts.main')
@section('main.content')
<style>
/* ── Stat cards ─────────────────────────── */
.jst { border-radius:10px; padding:.85rem 1rem; border:1px solid transparent; display:flex; align-items:center; gap:.85rem; }
.jst .ji { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.jst .jv { font-size:1.2rem; font-weight:800; line-height:1; }
.jst .jl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-top:.15rem; }
.js1 { background:#eff6ff; border-color:#bfdbfe; } .js1 .ji{background:#dbeafe;color:#1d4ed8} .js1 .jv,.js1 .jl{color:#1d4ed8}
.js2 { background:#f0fdf4; border-color:#bbf7d0; } .js2 .ji{background:#dcfce7;color:#15803d} .js2 .jv,.js2 .jl{color:#15803d}
.js3 { background:#fdf4ff; border-color:#e9d5ff; } .js3 .ji{background:#f3e8ff;color:#7e22ce} .js3 .jv,.js3 .jl{color:#7e22ce}

/* ── Journal card ───────────────────────── */
.jrnl-wrap { border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 4px 20px rgba(15,23,42,.06); overflow:hidden; }
.jrnl-filter { background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:.9rem 1.25rem; }
.jrnl-flabel { font-size:.63rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#475569; display:block; margin-bottom:.25rem; }

/* ── Single journal entry (voucher style) ─ */
.jv-entry {
    border-bottom: 1px solid #e9ecef;
    page-break-inside: avoid;
}
.jv-entry:last-child { border-bottom: none; }

.jv-top {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: .85rem 1.25rem .5rem;
    background: #fff;
    cursor: pointer;
    user-select: none;
    transition: background .12s;
}
.jv-top:hover { background: #f8faff; }

.jv-number {
    font-family: monospace;
    font-size: .75rem;
    font-weight: 800;
    color: #4f46e5;
    background: #e0e7ff;
    padding: 2px 8px;
    border-radius: 5px;
    white-space: nowrap;
    flex-shrink: 0;
    letter-spacing: .05em;
}
.jv-date {
    font-size: .82rem;
    font-weight: 700;
    color: #374151;
    white-space: nowrap;
    min-width: 90px;
    flex-shrink: 0;
}
.jv-badge { font-size:.64rem; font-weight:700; padding:2px 8px; border-radius:5px; white-space:nowrap; flex-shrink:0; }
.jv-ref-id { font-size:.7rem; color:#94a3b8; flex-shrink:0; }
.jv-memo {
    flex: 1;
    font-size: .855rem;
    color: #64748b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-style: italic;
}
.jv-totals {
    display: flex;
    gap: 1.25rem;
    align-items: center;
    flex-shrink: 0;
}
.jv-tot-item { text-align: right; }
.jv-tot-label { font-size: .6rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; display: block; }
.jv-tot-val   { font-size: .82rem; font-weight: 800; white-space: nowrap; }
.jv-dr-val    { color: #dc2626; }
.jv-cr-val    { color: #15803d; }
.jv-chevron   { color: #94a3b8; font-size: .85rem; flex-shrink: 0; transition: transform .2s; }
.jv-chevron.open { transform: rotate(90deg); color: #4f46e5; }

/* ── Entry lines (classic accounting format) ─ */
.jv-lines {
    display: none;
    padding: 0 1.25rem .85rem 1.25rem;
    background: #fafbfd;
}
.jv-lines.open { display: block; }

.jv-lines-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .845rem;
}
.jv-lines-table thead tr {
    border-bottom: 1px solid #d1d5db;
}
.jv-lines-table thead th {
    font-size: .62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #94a3b8;
    padding: .35rem .5rem;
}
.jv-lines-table tbody td {
    padding: .4rem .5rem;
    border-bottom: 1px dashed #f0f0f0;
    vertical-align: top;
}
.jv-lines-table tbody tr:last-child td { border-bottom: none; }

/* Classic accounting: credit lines indented */
.jv-line-dr .jv-acc-name {
    font-weight: 700;
    color: #0f172a;
}
.jv-line-cr .jv-acc-name {
    font-weight: 600;
    color: #374151;
    padding-left: 2rem;  /* indent credit lines */
}
.jv-line-cr .jv-acc-name::before {
    content: 'To ';
    color: #94a3b8;
    font-size: .78rem;
    font-style: italic;
    font-weight: 500;
}
.jv-line-dr .jv-acc-name::before {
    content: 'Dr ';
    color: #94a3b8;
    font-size: .78rem;
    font-style: italic;
    font-weight: 500;
}
.jv-acc-type { font-size: .65rem; color: #94a3b8; text-transform: capitalize; display: block; padding-left: 1.8rem; }
.jv-line-cr .jv-acc-type { padding-left: 3.8rem; }
.jv-narration {
    padding: .3rem .5rem .5rem 3.5rem;
    font-size: .78rem;
    color: #64748b;
    font-style: italic;
    border-top: 1px dashed #e9ecef;
}
.jv-narration::before { content: 'Being: '; font-weight: 700; color: #94a3b8; }
.jv-line-total {
    display: flex;
    justify-content: flex-end;
    gap: 3rem;
    padding: .35rem .5rem .1rem;
    border-top: 1px solid #e2e8f0;
    font-size: .72rem;
    color: #64748b;
}
.jv-line-total span { font-weight: 700; color: #374151; }

/* ── Period totals bar ──────────────────── */
.period-totals {
    background: linear-gradient(135deg,#1e293b 0%,#334155 100%);
    padding: .85rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.pt-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.5); display: block; }
.pt-val   { font-size: 1.1rem; font-weight: 900; color: #fff; }
.pt-dr-val { color: #fca5a5; }
.pt-cr-val { color: #86efac; }
.pt-balanced { font-size:.7rem; font-weight:700; padding:3px 10px; border-radius:6px; }

/* ── Reference badge colors ─────────────── */
.rb-sale     { background:#dcfce7; color:#15803d; }
.rb-purchase { background:#dbeafe; color:#1d4ed8; }
.rb-expense  { background:#fee2e2; color:#dc2626; }
.rb-manual   { background:#e0e7ff; color:#3730a3; }
.rb-transfer { background:#fef3c7; color:#92400e; }
.rb-deposit  { background:#dcfce7; color:#065f46; }
.rb-withdraw { background:#fee2e2; color:#991b1b; }
.rb-return   { background:#ede9fe; color:#5b21b6; }
.rb-default  { background:#f1f5f9; color:#475569; }

.empty-jv { text-align:center; padding:3.5rem 1rem; color:#94a3b8; }
.empty-jv i { font-size:3.5rem; opacity:.2; display:block; margin-bottom:.75rem; }

/* ── Print styles ───────────────────────── */
@media print {
    .jrnl-filter, .card-header, .page-title, nav, .sidebar,
    .btn, .breadcrumb, .dataTables_paginate, .dataTables_info { display:none !important; }
    .jv-entry { border-bottom: 1px solid #000 !important; }
    .jv-lines { display: block !important; }
    body { font-size: 11pt; }
    .jv-top { background: none !important; }
    .period-totals { background: #f0f0f0 !important; color: #000 !important; }
}
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Accounting Journal</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Journal</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-4">
            <div class="jst js1">
                <div class="ji"><i class="bi bi-journal-text"></i></div>
                <div>
                    <div class="jv">{{ number_format($stats['total']) }}</div>
                    <div class="jl">Total Entries</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="jst js2">
                <div class="ji"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="jv">{{ number_format($stats['this_month']) }}</div>
                    <div class="jl">This Month</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="jst js3">
                <div class="ji"><i class="bi bi-pencil-square"></i></div>
                <div>
                    <div class="jv">{{ number_format($stats['manual']) }}</div>
                    <div class="jl">Manual</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card jrnl-wrap">

            {{-- Header --}}
            <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2"
                 style="background:linear-gradient(135deg,#f8faff,#eef3ff);border-radius:0;border-bottom:1px solid #e2e8f0">
                <div>
                    <h5 class="mb-0 fw-bold">General Journal</h5>
                    <div class="text-muted small mt-1">Double-entry ledger — Dr entries flush left, Cr entries indented.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm fw-semibold">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <form method="post" action="{{ route('accounting.syncAutoJournals') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm fw-semibold">
                            <i class="bi bi-arrow-repeat me-1"></i>Sync
                        </button>
                    </form>
                    <a href="{{ route('accounting.journal.create') }}" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>New Entry
                    </a>
                    <a href="{{ route('accounting.transfer.create') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                        <i class="bi bi-arrow-left-right me-1"></i>Transfer
                    </a>
                </div>
            </div>

            {{-- Filters --}}
            <form method="get" action="{{ route('accounting.journal') }}">
                <div class="jrnl-filter">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2 col-6">
                            <span class="jrnl-flabel">Start Date</span>
                            <input type="date" name="startDate" value="{{ request('startDate') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 col-6">
                            <span class="jrnl-flabel">End Date</span>
                            <input type="date" name="endDate" value="{{ request('endDate') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 col-6">
                            <span class="jrnl-flabel">Entry Type</span>
                            <select name="ref_type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @foreach($refTypes as $rt)
                                <option value="{{ $rt }}" {{ request('ref_type') === $rt ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_',' ',$rt)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-6">
                            <span class="jrnl-flabel">Search JV / Narration</span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="JV no, memo...">
                        </div>
                        <div class="col-md-3 col-12 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-funnel-fill me-1"></i>Filter
                            </button>
                            <a href="{{ route('accounting.journal') }}" class="btn btn-light btn-sm" style="min-width:36px" title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                            {{-- Quick presets --}}
                            <div class="dropdown">
                                <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                    Quick
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="min-width:140px">
                                    <li><a class="dropdown-item small" href="{{ route('accounting.journal') }}?startDate={{ now()->format('Y-m-d') }}&endDate={{ now()->format('Y-m-d') }}">Today</a></li>
                                    <li><a class="dropdown-item small" href="{{ route('accounting.journal') }}?startDate={{ now()->startOfMonth()->format('Y-m-d') }}&endDate={{ now()->endOfMonth()->format('Y-m-d') }}">This Month</a></li>
                                    <li><a class="dropdown-item small" href="{{ route('accounting.journal') }}?startDate={{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}&endDate={{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}">Last Month</a></li>
                                    <li><a class="dropdown-item small" href="{{ route('accounting.journal') }}?startDate={{ now()->startOfYear()->format('Y-m-d') }}&endDate={{ now()->format('Y-m-d') }}">This Year</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item small text-primary" href="{{ route('accounting.journal') }}?ref_type=manual">Manual Only</a></li>
                                    <li><a class="dropdown-item small text-success" href="{{ route('accounting.journal') }}?ref_type=sale">Sales Only</a></li>
                                    <li><a class="dropdown-item small text-danger" href="{{ route('accounting.journal') }}?ref_type=expense">Expenses Only</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Journal entries --}}
            <div id="journalList">
                @forelse($journals as $journal)
                @php
                    $rt        = $journal->reference_type ?? '';
                    $jvNo      = 'JV-' . str_pad($journal->id, 6, '0', STR_PAD_LEFT);
                    $totalDr   = $journal->lines->sum('debit');
                    $totalCr   = $journal->lines->sum('credit');
                    $balanced  = round($totalDr, 2) === round($totalCr, 2);
                    $drLines   = $journal->lines->where('debit',  '>', 0);
                    $crLines   = $journal->lines->where('credit', '>', 0);

                    $badgeClass = match(true) {
                        str_contains($rt,'sale') && !str_contains($rt,'return') => 'rb-sale',
                        str_contains($rt,'purchase')  => 'rb-purchase',
                        str_contains($rt,'expense')   => 'rb-expense',
                        $rt === 'manual'              => 'rb-manual',
                        str_contains($rt,'transfer')  => 'rb-transfer',
                        $rt === 'deposit'             => 'rb-deposit',
                        $rt === 'withdrawal'          => 'rb-withdraw',
                        str_contains($rt,'return')    => 'rb-return',
                        default                       => 'rb-default',
                    };
                    $badgeLabel = ucwords(str_replace('_',' ',$rt));
                @endphp
                <div class="jv-entry" id="jv-{{ $journal->id }}">
                    {{-- Entry header row --}}
                    <div class="jv-top" onclick="toggleJV(this)">
                        <i class="bi bi-chevron-right jv-chevron"></i>
                        <span class="jv-number">{{ $jvNo }}</span>
                        <span class="jv-date">{{ \App\Support\DateFormatter::date($journal->journal_date) }}</span>
                        <span class="jv-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        @if($journal->reference_id)
                        <span class="jv-ref-id">#{{ $journal->reference_id }}</span>
                        @endif
                        <span class="jv-memo" title="{{ $journal->memo }}">{{ $journal->memo ?? 'No narration' }}</span>
                        <div class="jv-totals">
                            <div class="jv-tot-item">
                                <span class="jv-tot-label">Debit</span>
                                <span class="jv-tot-val jv-dr-val">{{ number_format($totalDr,2) }}</span>
                            </div>
                            <div class="jv-tot-item">
                                <span class="jv-tot-label">Credit</span>
                                <span class="jv-tot-val jv-cr-val">{{ number_format($totalCr,2) }}</span>
                            </div>
                            @if(!$balanced)
                            <span title="Unbalanced!" style="color:#dc2626;font-size:.9rem"><i class="bi bi-exclamation-triangle-fill"></i></span>
                            @endif
                        </div>
                    </div>

                    {{-- Entry lines in classic accounting format --}}
                    <div class="jv-lines">
                        <table class="jv-lines-table">
                            <thead>
                                <tr>
                                    <th style="width:55%">Particulars</th>
                                    <th style="width:18%">Branch</th>
                                    <th class="text-end" style="width:13%">Debit (৳)</th>
                                    <th class="text-end" style="width:14%">Credit (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Debit lines first --}}
                                @foreach($drLines as $line)
                                <tr class="jv-line-dr">
                                    <td>
                                        <span class="jv-acc-name">{{ $line->account?->account_name ?? 'Unknown Account' }}</span>
                                        <span class="jv-acc-type">{{ $line->account?->account_type }}</span>
                                    </td>
                                    <td style="font-size:.78rem;color:#64748b">{{ $line->branch?->name ?? 'Global' }}</td>
                                    <td class="text-end" style="font-weight:800;color:#dc2626">{{ number_format($line->debit,2) }}</td>
                                    <td class="text-end text-muted">—</td>
                                </tr>
                                @endforeach

                                {{-- Credit lines after, indented --}}
                                @foreach($crLines as $line)
                                <tr class="jv-line-cr">
                                    <td>
                                        <span class="jv-acc-name">{{ $line->account?->account_name ?? 'Unknown Account' }}</span>
                                        <span class="jv-acc-type">{{ $line->account?->account_type }}</span>
                                    </td>
                                    <td style="font-size:.78rem;color:#64748b">{{ $line->branch?->name ?? 'Global' }}</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end" style="font-weight:800;color:#15803d">{{ number_format($line->credit,2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Narration line --}}
                        @if($journal->memo)
                        <div class="jv-narration">{{ $journal->memo }}</div>
                        @endif

                        {{-- Entry totals --}}
                        <div class="jv-line-total">
                            <div>Total: &nbsp; <span>{{ number_format($totalDr,2) }}</span></div>
                            <div>&nbsp; <span>{{ number_format($totalCr,2) }}</span></div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-jv">
                    <i class="bi bi-journal-x"></i>
                    <div class="fw-semibold">No journal entries found.</div>
                    <div class="small mt-1 mb-3">Use <strong>Sync</strong> to auto-generate from sales/purchases, or create a <strong>New Entry</strong>.</div>
                </div>
                @endforelse
            </div>

            {{-- Period totals bar --}}
            @if($journals->isNotEmpty())
            @php
                $ptDr  = (float)($periodTotals?->total_dr  ?? 0);
                $ptCr  = (float)($periodTotals?->total_cr  ?? 0);
                $ptBal = round($ptDr,2) === round($ptCr,2);
            @endphp
            <div class="period-totals">
                <div>
                    <span class="pt-label">Period Summary</span>
                    <span style="color:rgba(255,255,255,.6);font-size:.78rem">
                        {{ $journals->total() }} entries
                        @if(request('startDate') || request('endDate'))
                        &nbsp;·&nbsp;
                        {{ request('startDate') ? \App\Support\DateFormatter::date(request('startDate')) : 'All time' }}
                        @if(request('endDate')) → {{ \App\Support\DateFormatter::date(request('endDate')) }} @endif
                        @endif
                    </span>
                </div>
                <div class="d-flex gap-4 align-items-center">
                    <div class="text-end">
                        <span class="pt-label">Total Debit</span>
                        <div class="pt-val pt-dr-val">৳ {{ number_format($ptDr,2) }}</div>
                    </div>
                    <div class="text-end">
                        <span class="pt-label">Total Credit</span>
                        <div class="pt-val pt-cr-val">৳ {{ number_format($ptCr,2) }}</div>
                    </div>
                    <div class="text-end">
                        <span class="pt-label">Status</span>
                        <span class="pt-balanced {{ $ptBal ? 'bg-success' : 'bg-danger' }} text-white">
                            {{ $ptBal ? '✓ Balanced' : '✗ Unbalanced' }}
                        </span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                <div style="font-size:.78rem;color:#64748b">
                    Showing {{ $journals->firstItem() }}–{{ $journals->lastItem() }} of {{ $journals->total() }} entries
                </div>
                {{ $journals->links() }}
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
function toggleJV(header) {
    var chevron = header.querySelector('.jv-chevron');
    var lines   = header.nextElementSibling;
    var open    = lines.classList.toggle('open');
    chevron.classList.toggle('open', open);
    header.style.background = open ? '#f0f5ff' : '';
}

// Expand all / collapse all
document.addEventListener('keydown', function(e) {
    if (e.key === 'e' && e.ctrlKey) {
        e.preventDefault();
        document.querySelectorAll('.jv-lines').forEach(el => el.classList.add('open'));
        document.querySelectorAll('.jv-chevron').forEach(el => el.classList.add('open'));
    }
    if (e.key === 'r' && e.ctrlKey) {
        e.preventDefault();
        document.querySelectorAll('.jv-lines').forEach(el => el.classList.remove('open'));
        document.querySelectorAll('.jv-chevron').forEach(el => el.classList.remove('open'));
    }
});
</script>
@endsection
