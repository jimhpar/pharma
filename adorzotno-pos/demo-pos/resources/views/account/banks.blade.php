@extends('layouts.main')
@section('main.content')
<style>
/* ── Summary cards ──────────────────── */
.bk-sum { border-radius:12px; padding:1rem 1.25rem; display:flex; align-items:center; gap:1rem; border:1px solid transparent; }
.bk-sum .bsi { width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0; }
.bk-sum .bsv { font-size:1.35rem;font-weight:800;line-height:1.1; }
.bk-sum .bsl { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-top:.2rem; }
.bk-s1 { background:#eff6ff;border-color:#bfdbfe; } .bk-s1 .bsi{background:#dbeafe;color:#1d4ed8} .bk-s1 .bsv,.bk-s1 .bsl{color:#1d4ed8}
.bk-s2 { background:#f0fdf4;border-color:#bbf7d0; } .bk-s2 .bsi{background:#dcfce7;color:#15803d} .bk-s2 .bsv,.bk-s2 .bsl{color:#15803d}
.bk-s3 { background:#fdf4ff;border-color:#e9d5ff; } .bk-s3 .bsi{background:#f3e8ff;color:#7e22ce} .bk-s3 .bsv,.bk-s3 .bsl{color:#7e22ce}

/* ── Bank account card ──────────────── */
.bank-card {
    border-radius:14px;
    border:1px solid #e2e8f0;
    overflow:hidden;
    box-shadow:0 4px 18px rgba(15,23,42,.07);
    transition:box-shadow .2s, transform .15s;
}
.bank-card:hover { box-shadow:0 8px 28px rgba(15,23,42,.12); transform:translateY(-2px); }
.bank-card-header {
    padding:1.25rem 1.25rem .9rem;
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    position:relative;
    overflow:hidden;
}
.bank-card-header::before {
    content:'';
    position:absolute;
    top:-30px;right:-30px;
    width:100px;height:100px;
    border-radius:50%;
    background:rgba(255,255,255,.08);
}
.bank-card-header.type-mobile {
    background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%);
}
.bk-name  { font-size:1rem;font-weight:800;color:#fff;line-height:1.2; }
.bk-no    { font-size:.72rem;font-weight:600;color:rgba(255,255,255,.75);margin-top:.2rem;font-family:monospace;letter-spacing:.05em; }
.bk-branch{ font-size:.7rem;color:rgba(255,255,255,.6);margin-top:.1rem; }
.bk-type-pill { display:inline-block;background:rgba(255,255,255,.2);color:#fff;font-size:.6rem;font-weight:800;padding:2px 8px;border-radius:10px;text-transform:uppercase;letter-spacing:.05em; }

.bank-card-body { padding:1.1rem 1.25rem; background:#fff; }
.bk-balance-label { font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8; }
.bk-balance-value { font-size:1.5rem;font-weight:900;color:#0f172a;line-height:1.1; }
.bk-balance-value.positive { color:#15803d; }
.bk-balance-value.negative { color:#dc2626; }
.bk-opening { font-size:.72rem;color:#94a3b8;margin-top:.2rem; }

.bank-card-footer { padding:.75rem 1.25rem;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;gap:.5rem; }
.btn-deposit  { background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;font-size:.78rem;font-weight:700;padding:.3rem .85rem;border-radius:7px;cursor:pointer;transition:all .15s; }
.btn-deposit:hover  { background:#15803d;color:#fff; }
.btn-withdraw { background:#fee2e2;color:#dc2626;border:1px solid #fecaca;font-size:.78rem;font-weight:700;padding:.3rem .85rem;border-radius:7px;cursor:pointer;transition:all .15s; }
.btn-withdraw:hover { background:#dc2626;color:#fff; }
.btn-statement{ background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:.78rem;font-weight:700;padding:.3rem .85rem;border-radius:7px;cursor:pointer;transition:all .15s; }
.btn-statement:hover{ background:#1d4ed8;color:#fff; }
.btn-edit-bk  { background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;font-size:.78rem;font-weight:600;padding:.3rem .75rem;border-radius:7px;cursor:pointer;transition:all .15s;margin-left:auto; }
.btn-edit-bk:hover  { background:#475569;color:#fff; }

.empty-banks { text-align:center;padding:3rem 1rem;color:#94a3b8; }
.empty-banks i { font-size:3.5rem;opacity:.25;display:block;margin-bottom:.75rem; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Bank Accounts</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Bank Accounts</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="bk-sum bk-s1">
                <div class="bsi"><i class="bi bi-bank"></i></div>
                <div>
                    <div class="bsv">{{ $bankAccounts->count() }}</div>
                    <div class="bsl">Bank Accounts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="bk-sum bk-s2">
                <div class="bsi"><i class="bi bi-currency-dollar"></i></div>
                <div>
                    <div class="bsv" style="font-size:1.1rem">৳ {{ number_format($totalBalance, 2) }}</div>
                    <div class="bsl">Total Bank Balance</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="bk-sum bk-s3">
                <div class="bsi"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="bsv" style="font-size:1.1rem">৳ {{ number_format($totalCash, 2) }}</div>
                    <div class="bsl">Cash Balance</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">All Bank &amp; Mobile Banking Accounts</h5>
            <div class="text-muted small">Click Deposit / Withdraw to record transactions.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.reports') }}?report=bank_book" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-book me-1"></i>Bank Book
            </a>
            <a href="{{ route('account.create') }}" class="btn btn-primary btn-sm fw-semibold">
                <i class="bi bi-plus-lg me-1"></i>Add Account
            </a>
        </div>
    </div>

    {{-- Bank cards grid --}}
    @if($bankAccounts->isEmpty())
    <div class="card" style="border-radius:12px;border:1px solid #e2e8f0">
        <div class="empty-banks">
            <i class="bi bi-bank"></i>
            <div class="fw-semibold">No bank accounts added yet.</div>
            <div class="small mt-1 mb-3">Add your first bank or mobile banking account.</div>
            <a href="{{ route('account.create') }}?type=bank" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Bank Account
            </a>
        </div>
    </div>
    @else
    <div class="row g-3">
        @foreach($bankAccounts as $account)
        @php
            $bal = $account->current_balance_live;
            $isMobile = $account->account_type === 'mobile_banking';
        @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="bank-card">
                <div class="bank-card-header {{ $isMobile ? 'type-mobile' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="bk-name">{{ $account->bank_name ?: $account->account_name }}</div>
                            @if($account->account_number)
                            <div class="bk-no"><i class="bi bi-credit-card me-1"></i>{{ $account->account_number }}</div>
                            @endif
                            @if($account->bank_branch)
                            <div class="bk-branch"><i class="bi bi-geo-alt me-1"></i>{{ $account->bank_branch }}</div>
                            @endif
                        </div>
                        <span class="bk-type-pill">{{ $isMobile ? 'Mobile' : 'Bank' }}</span>
                    </div>
                </div>
                <div class="bank-card-body">
                    <div class="row g-3">
                        <div class="col-7">
                            <div class="bk-balance-label">Current Balance</div>
                            <div class="bk-balance-value {{ $bal >= 0 ? 'positive' : 'negative' }}">
                                ৳ {{ number_format(abs($bal), 2) }}
                                @if($bal < 0)<small class="text-danger">(OD)</small>@endif
                            </div>
                            <div class="bk-opening">Opening: ৳ {{ number_format($account->opening_balance, 2) }}</div>
                        </div>
                        <div class="col-5">
                            @if($account->account_name !== ($account->bank_name ?: $account->account_name))
                            <div class="bk-balance-label">Account Title</div>
                            <div style="font-size:.8rem;font-weight:600;color:#374151">{{ $account->account_name }}</div>
                            @endif
                            @if($account->routing_number)
                            <div class="bk-balance-label mt-2">Routing</div>
                            <div style="font-size:.75rem;font-family:monospace;color:#64748b">{{ $account->routing_number }}</div>
                            @endif
                            @if($account->account_code)
                            <div class="bk-balance-label mt-2">Code</div>
                            <div style="font-size:.75rem;color:#64748b">{{ $account->account_code }}</div>
                            @endif
                        </div>
                    </div>
                    @if($account->notes)
                    <div class="mt-2 small text-muted" style="font-style:italic">{{ $account->notes }}</div>
                    @endif
                </div>
                <div class="bank-card-footer">
                    <button class="btn-deposit" onclick="openDeposit({{ $account->id }}, '{{ addslashes($account->account_name) }}')">
                        <i class="bi bi-arrow-down-circle me-1"></i>Deposit
                    </button>
                    <button class="btn-withdraw" onclick="openWithdraw({{ $account->id }}, '{{ addslashes($account->account_name) }}')">
                        <i class="bi bi-arrow-up-circle me-1"></i>Withdraw
                    </button>
                    <a class="btn-statement" href="{{ route('accounting.reports') }}?report=bank_book&account_id={{ $account->id }}">
                        <i class="bi bi-journal-text me-1"></i>Statement
                    </a>
                    <a class="btn-edit-bk" href="{{ route('account.edit', $account->id) }}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ── Deposit Modal ──────────────────────────── --}}
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden">
            <div class="modal-header" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-bottom:1px solid #86efac">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-arrow-down-circle me-2"></i>Record Deposit</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0">
                    <div class="small text-muted">Depositing to</div>
                    <div id="dep-acc-name" class="fw-bold text-success"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Date <span class="text-danger">*</span></label>
                    <input type="date" id="dep-date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">৳</span>
                        <input type="number" id="dep-amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" style="font-size:1.2rem;font-weight:800">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Source (Cash from)</label>
                    <select id="dep-source" class="form-select">
                        <option value="">Cash In Hand (default)</option>
                        @foreach(\App\Models\Account::where('status','active')->whereNotIn('account_type',['expense','income','receivable','payable'])->orderBy('account_name')->get() as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ ucwords(str_replace('_',' ',$acc->account_type)) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold mb-1">Description</label>
                    <input type="text" id="dep-desc" class="form-control" placeholder="e.g. Cash deposit, cheque deposit...">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-4" onclick="submitDeposit()">
                    <i class="bi bi-check-lg me-1"></i>Record Deposit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Withdraw Modal ─────────────────────────── --}}
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden">
            <div class="modal-header" style="background:linear-gradient(135deg,#fee2e2,#fecaca);border-bottom:1px solid #fca5a5">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-arrow-up-circle me-2"></i>Record Withdrawal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-2 rounded" style="background:#fef2f2;border:1px solid #fecaca">
                    <div class="small text-muted">Withdrawing from</div>
                    <div id="wd-acc-name" class="fw-bold text-danger"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Date <span class="text-danger">*</span></label>
                    <input type="date" id="wd-date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">৳</span>
                        <input type="number" id="wd-amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" style="font-size:1.2rem;font-weight:800">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Transfer To (destination)</label>
                    <select id="wd-dest" class="form-select">
                        <option value="">Cash In Hand (default)</option>
                        @foreach(\App\Models\Account::where('status','active')->orderBy('account_name')->get() as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ ucwords(str_replace('_',' ',$acc->account_type)) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold mb-1">Description</label>
                    <input type="text" id="wd-desc" class="form-control" placeholder="e.g. Cash withdrawal, expense payment...">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm fw-bold px-4" onclick="submitWithdraw()">
                    <i class="bi bi-check-lg me-1"></i>Record Withdrawal
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer.js')
<script>
var activeAccountId = null;

function openDeposit(id, name) {
    activeAccountId = id;
    document.getElementById('dep-acc-name').textContent = name;
    document.getElementById('dep-amount').value = '';
    document.getElementById('dep-desc').value = '';
    document.getElementById('dep-source').value = '';
    document.getElementById('dep-date').value = '{{ now()->format("Y-m-d") }}';
    new bootstrap.Modal(document.getElementById('depositModal')).show();
}

function openWithdraw(id, name) {
    activeAccountId = id;
    document.getElementById('wd-acc-name').textContent = name;
    document.getElementById('wd-amount').value = '';
    document.getElementById('wd-desc').value = '';
    document.getElementById('wd-dest').value = '';
    document.getElementById('wd-date').value = '{{ now()->format("Y-m-d") }}';
    new bootstrap.Modal(document.getElementById('withdrawModal')).show();
}

function submitDeposit() {
    var amount = parseFloat(document.getElementById('dep-amount').value || 0);
    if (!activeAccountId || amount <= 0) { toastr.error('Please enter a valid amount.'); return; }

    $.post('{{ route("account.deposit") }}', {
        _token:            '{{ csrf_token() }}',
        account_id:        activeAccountId,
        amount:            amount,
        date:              document.getElementById('dep-date').value,
        source_account_id: document.getElementById('dep-source').value || null,
        description:       document.getElementById('dep-desc').value,
    }, function(res) {
        toastr.success(res.message);
        bootstrap.Modal.getInstance(document.getElementById('depositModal')).hide();
        setTimeout(() => location.reload(), 800);
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || 'Failed to record deposit.');
    });
}

function submitWithdraw() {
    var amount = parseFloat(document.getElementById('wd-amount').value || 0);
    if (!activeAccountId || amount <= 0) { toastr.error('Please enter a valid amount.'); return; }

    $.post('{{ route("account.withdraw") }}', {
        _token:                  '{{ csrf_token() }}',
        account_id:              activeAccountId,
        amount:                  amount,
        date:                    document.getElementById('wd-date').value,
        destination_account_id:  document.getElementById('wd-dest').value || null,
        description:             document.getElementById('wd-desc').value,
    }, function(res) {
        toastr.success(res.message);
        bootstrap.Modal.getInstance(document.getElementById('withdrawModal')).hide();
        setTimeout(() => location.reload(), 800);
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || 'Failed to record withdrawal.');
    });
}
</script>
@endsection
