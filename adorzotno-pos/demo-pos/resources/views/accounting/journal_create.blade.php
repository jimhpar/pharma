@extends('layouts.main')
@section('main.content')
@php $nextJvNo = 'JV-' . str_pad(\App\Models\AccountingJournal::max('id') + 1, 6, '0', STR_PAD_LEFT); @endphp
<style>
.jc-card { border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 4px 20px rgba(15,23,42,.06); overflow:hidden; }
.jc-head  { background:linear-gradient(135deg,#f8faff,#eef3ff); border-bottom:1px solid #e2e8f0; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; }
.jv-badge { background:#4f46e5; color:#fff; font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:6px; font-family:monospace; letter-spacing:.05em; }

.jc-lines-table thead th { background:#f0f4fa; font-size:.66rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; padding:.55rem .65rem; color:#374151; border-bottom:2px solid #d1d5db; white-space:nowrap; }
.jc-lines-table tbody td { padding:.4rem .5rem; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
.jc-lines-table tbody tr:hover { background:#f8faff; }
.jc-lines-table .form-control,.jc-lines-table .form-select { font-size:.855rem; padding:.3rem .5rem; }

.balance-indicator { border-radius:10px; padding:.75rem 1.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; }
.balance-ok   { background:#dcfce7; border:1px solid #86efac; }
.balance-off  { background:#fee2e2; border:1px solid #fca5a5; }
.bal-item { text-align:center; min-width:120px; }
.bal-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
.bal-value { font-size:1.1rem; font-weight:900; }
.bal-dr { color:#dc2626; }
.bal-cr { color:#15803d; }
.bal-diff.ok   { color:#15803d; font-size:1.1rem; font-weight:900; }
.bal-diff.off  { color:#dc2626; font-size:1.1rem; font-weight:900; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Manual Journal Entry</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('accounting.journal') }}">Journals</a></li>
                        <li class="breadcrumb-item active">New Entry</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card jc-card">
            <div class="jc-head">
                <div>
                    <h5 class="mb-0 fw-bold">Journal Entry
                        <span class="jv-badge ms-2">{{ $nextJvNo }}</span>
                    </h5>
                    <div class="text-muted small mt-1">Debit total must equal Credit total before posting.</div>
                </div>
                <a href="{{ route('accounting.journal') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('accounting.journal.store') }}" id="jForm">
                    @csrf

                    @error('lines')
                    <div class="alert alert-danger py-2">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>{{ $message }}</strong>
                    </div>
                    @enderror

                    {{-- Header fields --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-semibold mb-1">Journal Date <span class="text-danger">*</span></label>
                            <input type="date" name="journal_date"
                                value="{{ old('journal_date', now()->format('Y-m-d')) }}"
                                class="form-control @error('journal_date') is-invalid @enderror">
                            @error('journal_date')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-semibold mb-1">Memo / Narration</label>
                            <input name="memo" value="{{ old('memo') }}"
                                class="form-control"
                                placeholder="Brief description of this journal entry…">
                        </div>
                    </div>

                    {{-- Lines table --}}
                    <div class="table-responsive mb-3">
                        <table class="table jc-lines-table align-middle mb-0" id="journalLines">
                            <thead>
                                <tr>
                                    <th style="width:30px">#</th>
                                    <th style="min-width:220px">Account <span class="text-danger">*</span></th>
                                    <th style="min-width:140px">Branch</th>
                                    <th style="width:130px">Debit (Dr)</th>
                                    <th style="width:130px">Credit (Cr)</th>
                                    <th style="min-width:180px">Description</th>
                                    <th style="width:36px"></th>
                                </tr>
                            </thead>
                            <tbody id="jLinesBody">
                                @for($i = 0; $i < 4; $i++)
                                <tr>
                                    <td class="text-center text-muted fw-semibold line-num">{{ $i+1 }}</td>
                                    <td>
                                        <select name="lines[{{ $i }}][account_id]" class="form-select">
                                            <option value="">— Select Account —</option>
                                            @foreach($accounts->groupBy('account_type') as $type => $group)
                                            <optgroup label="{{ ucwords($type) }}">
                                                @foreach($group as $acc)
                                                <option value="{{ $acc->id }}" {{ old("lines.$i.account_id") == $acc->id ? 'selected' : '' }}>
                                                    {{ $acc->account_name }}
                                                    @if($acc->account_code) ({{ $acc->account_code }}) @endif
                                                </option>
                                                @endforeach
                                            </optgroup>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="lines[{{ $i }}][branch_id]" class="form-select">
                                            <option value="">Global</option>
                                            @foreach($branches as $b)
                                            <option value="{{ $b->id }}" {{ old("lines.$i.branch_id") == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            name="lines[{{ $i }}][debit]"
                                            class="form-control jl-debit"
                                            value="{{ old("lines.$i.debit") }}"
                                            placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            name="lines[{{ $i }}][credit]"
                                            class="form-control jl-credit"
                                            value="{{ old("lines.$i.credit") }}"
                                            placeholder="0.00">
                                    </td>
                                    <td>
                                        <input name="lines[{{ $i }}][description]"
                                            class="form-control"
                                            value="{{ old("lines.$i.description") }}"
                                            placeholder="Optional narration">
                                    </td>
                                    <td class="text-center">
                                        @if($i > 1)
                                        <button type="button" class="btn-remove-jl" onclick="removeJLine(this)" title="Remove">&times;</button>
                                        @endif
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addJLine()">
                        <i class="bi bi-plus-lg me-1"></i>Add Line
                    </button>

                    {{-- Balance Indicator --}}
                    <div class="balance-indicator balance-off mb-4" id="balanceIndicator">
                        <div class="bal-item">
                            <div class="bal-label">Total Debit (Dr)</div>
                            <div class="bal-value bal-dr" id="totalDr">0.00</div>
                        </div>
                        <div class="bal-item">
                            <div class="bal-label">Total Credit (Cr)</div>
                            <div class="bal-value bal-cr" id="totalCr">0.00</div>
                        </div>
                        <div class="bal-item">
                            <div class="bal-label">Difference</div>
                            <div class="bal-diff off" id="balDiff">0.00</div>
                        </div>
                        <div class="bal-item">
                            <div class="bal-label">Status</div>
                            <div id="balStatus" style="font-size:.85rem;font-weight:700">Enter amounts</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('accounting.journal') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary fw-semibold px-4" id="postBtn">
                            <i class="bi bi-check-lg me-1"></i>Post Journal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<style>
.btn-remove-jl { padding:.2rem .55rem; border-radius:50%; line-height:1; font-size:1.05rem; border:none; background:transparent; color:#dc3545; cursor:pointer; transition:background .15s; }
.btn-remove-jl:hover { background:#fde8ea; }
</style>
<script>
var jLineCount = 4;
var accounts = @json($accounts->map(fn($a) => ['id'=>$a->id,'name'=>$a->account_name,'code'=>$a->account_code,'type'=>$a->account_type])->values());
var branches = @json($branches->map(fn($b) => ['id'=>$b->id,'name'=>$b->name])->values());

function buildAccountSelect(idx) {
    var types = {};
    accounts.forEach(function(a) { if (!types[a.type]) types[a.type] = []; types[a.type].push(a); });
    var html = '<option value="">— Select Account —</option>';
    Object.keys(types).sort().forEach(function(t) {
        html += '<optgroup label="'+t.charAt(0).toUpperCase()+t.slice(1)+'">';
        types[t].forEach(function(a){ html += '<option value="'+a.id+'">'+a.name+(a.code?' ('+a.code+')':'')+'</option>'; });
        html += '</optgroup>';
    });
    return html;
}

function buildBranchSelect(idx) {
    var html = '<option value="">Global</option>';
    branches.forEach(function(b){ html += '<option value="'+b.id+'">'+b.name+'</option>'; });
    return html;
}

function addJLine() {
    var i = jLineCount;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td class="text-center text-muted fw-semibold line-num"></td>'
        + '<td><select name="lines['+i+'][account_id]" class="form-select">'+buildAccountSelect(i)+'</select></td>'
        + '<td><select name="lines['+i+'][branch_id]" class="form-select">'+buildBranchSelect(i)+'</select></td>'
        + '<td><input type="number" step="0.01" min="0" name="lines['+i+'][debit]" class="form-control jl-debit" placeholder="0.00"></td>'
        + '<td><input type="number" step="0.01" min="0" name="lines['+i+'][credit]" class="form-control jl-credit" placeholder="0.00"></td>'
        + '<td><input name="lines['+i+'][description]" class="form-control" placeholder="Optional narration"></td>'
        + '<td class="text-center"><button type="button" class="btn-remove-jl" onclick="removeJLine(this)" title="Remove">&times;</button></td>';
    document.getElementById('jLinesBody').appendChild(tr);
    if (typeof initSelect2 === 'function') initSelect2(tr);
    jLineCount++;
    updateLineNums();
    updateBalance();
}

function removeJLine(btn) {
    var rows = document.querySelectorAll('#jLinesBody tr');
    if (rows.length <= 2) return;
    btn.closest('tr').remove();
    updateLineNums();
    updateBalance();
}

function updateLineNums() {
    document.querySelectorAll('#jLinesBody .line-num').forEach(function(c,i){ c.textContent = i+1; });
}

function updateBalance() {
    var dr = 0, cr = 0;
    document.querySelectorAll('.jl-debit').forEach(function(el){ dr += parseFloat(el.value||0); });
    document.querySelectorAll('.jl-credit').forEach(function(el){ cr += parseFloat(el.value||0); });
    var diff = Math.abs(dr - cr);
    var balanced = dr > 0 && Math.round(diff*100) === 0;

    document.getElementById('totalDr').textContent   = dr.toFixed(2);
    document.getElementById('totalCr').textContent   = cr.toFixed(2);
    document.getElementById('balDiff').textContent   = diff.toFixed(2);
    document.getElementById('balDiff').className     = 'bal-diff ' + (balanced ? 'ok' : 'off');
    document.getElementById('balStatus').textContent = balanced ? '✓ Balanced' : (dr === 0 && cr === 0 ? 'Enter amounts' : '✗ Not balanced');
    document.getElementById('balStatus').style.color = balanced ? '#15803d' : '#dc2626';

    var ind = document.getElementById('balanceIndicator');
    ind.classList.toggle('balance-ok',  balanced);
    ind.classList.toggle('balance-off', !balanced);

    document.getElementById('postBtn').disabled = !balanced;
}

document.getElementById('jLinesBody').addEventListener('input', function(e){
    if (e.target.classList.contains('jl-debit') || e.target.classList.contains('jl-credit')) updateBalance();
});

updateBalance();
</script>
@endsection
