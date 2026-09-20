@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
/* ── Commission report extras ── */
.cm-avatar { display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;font-size:.72rem;font-weight:800;color:#fff;flex-shrink:0; }
.cm-person-row { background:#f8faff!important; }
.cm-person-row td { font-weight:700;color:#1e293b;border-top:2px solid #c7d2fe!important; }
.cm-month-row td { padding:.45rem .75rem!important;font-size:.82rem; }
.cm-total-row td { background:#f0f4fa!important;font-weight:800;font-size:.82rem;border-top:2px solid #d1d5db!important; }
.rate-badge { display:inline-block;background:#eff6ff;color:#1d4ed8;font-size:.65rem;font-weight:800;border-radius:5px;padding:2px 7px; }
.plan-badge { display:inline-block;background:#f3e8ff;color:#6d28d9;font-size:.65rem;font-weight:800;border-radius:5px;padding:2px 7px;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
#commTable thead th { padding:.55rem .75rem; }

@media print {
    .no-print { display:none!important; }
    .rpt-card { box-shadow:none!important;border:1px solid #ccc!important; }
    body { font-size:11px; }
}
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Sales Commission Report</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Commission Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- Summary stat cards (filled after generate) --}}
    <div class="row g-3 mb-3" id="summaryCards" style="display:none!important">
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c1">
                <div class="rsi"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="rsv" id="sPersonCount">0</div>
                    <div class="rsl">Salespersons</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c2">
                <div class="rsi"><i class="bi bi-bag-fill"></i></div>
                <div>
                    <div class="rsv" id="sOrderCount">0</div>
                    <div class="rsl">Total Orders</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c4">
                <div class="rsi"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="rsv" id="sTotalSales" style="font-size:1rem">—</div>
                    <div class="rsl">Total Sales</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rpt-stat rpt-c3">
                <div class="rsi"><i class="bi bi-award-fill"></i></div>
                <div>
                    <div class="rsv" id="sTotalCommission" style="font-size:1rem;color:#c2410c">—</div>
                    <div class="rsl">Total Commission</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head no-print">
                <div>
                    <div class="rpt-head-title">Month-wise Commission Calculator</div>
                    <div class="rpt-head-sub">
                        Shows sales per salesperson and calculates commission based on their assigned plan.
                        <a href="{{ route('salesCommissionPlan.show') }}" class="text-primary ms-1" style="font-size:.75rem">
                            <i class="bi bi-gear-fill me-1"></i>Manage Commission Plans
                        </a>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success fw-bold" id="excelBtn" style="display:none" onclick="doExcel()">
                        <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel
                    </button>
                    <button class="btn btn-secondary fw-bold" id="printBtn" style="display:none" onclick="window.print()">
                        <i class="bi bi-printer-fill pe-1 fs-5"></i> Print / PDF
                    </button>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div class="rpt-filter no-print">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Year <span style="color:#ef4444">*</span></span>
                        <select id="yearSel" class="form-select form-select-sm">
                            @for($y = now()->year; $y >= now()->year - 4; $y--)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <span class="rpt-flabel">Month</span>
                        <select id="monthSel" class="form-select form-select-sm">
                            <option value="">Full Year</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">Salesperson</span>
                        <select id="userSel" class="form-select form-select-sm">
                            <option value="">All Salespersons</option>
                            @foreach($salespersons as $sp)
                                <option value="{{ $sp->user_id }}">
                                    {{ $sp->user?->name ?? 'User #'.$sp->user_id }}
                                    @if($sp->employee_code) ({{ $sp->employee_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="rpt-flabel">
                            Commission Rate Override (%)
                            <span style="color:#64748b;font-weight:600" title="Overrides assigned plan. Leave empty to use each person's plan.">
                                <i class="bi bi-info-circle"></i>
                            </span>
                        </span>
                        <div class="input-group input-group-sm">
                            <input type="number" id="customRate" class="form-control form-control-sm" placeholder="e.g. 5"
                                   min="0" max="100" step="0.01" style="max-width:100px">
                            <span class="input-group-text">%</span>
                            <span class="input-group-text text-muted" style="font-size:.65rem;background:#f8fafc">
                                of Gross Sales
                            </span>
                        </div>
                        <div style="font-size:.65rem;color:#94a3b8;margin-top:.2rem">Empty = use each person's assigned plan</div>
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1 align-items-end">
                        <button id="generateBtn" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="bi bi-bar-chart-fill me-1"></i>Generate
                        </button>
                        <button id="resetBtn" class="btn btn-light btn-sm" style="min-width:36px">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>

                {{-- Active plans info --}}
                @if($plans->isNotEmpty())
                <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
                    <span style="font-size:.62rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em">Active Plans:</span>
                    @foreach($plans as $plan)
                    <span style="background:#f3e8ff;color:#6d28d9;font-size:.65rem;font-weight:700;border-radius:5px;padding:2px 8px">
                        {{ $plan->name }} —
                        {{ $plan->calculation_type === 'percentage' ? $plan->rate.'%' : '৳'.number_format($plan->rate,2) }}
                        of {{ str_replace('_', ' ', $plan->base_amount_type) }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Loading spinner --}}
            <div id="loadingRow" class="text-center py-5" style="display:none">
                <div class="spinner-border text-primary" style="width:2rem;height:2rem"></div>
                <div class="text-muted mt-2 small">Calculating commission data...</div>
            </div>

            {{-- Empty state --}}
            <div id="emptyState" class="text-center py-5 text-muted">
                <i class="bi bi-bar-chart-line fs-2 d-block mb-2 text-primary" style="opacity:.4"></i>
                <div class="fw-semibold">Select filters and click <strong>Generate</strong></div>
                <div class="small mt-1">Commission will be calculated based on each salesperson's assigned plan.</div>
            </div>

            {{-- Results table --}}
            <div id="resultsWrap" style="display:none">
                <div class="card-body p-0">
                    <div style="overflow-x:auto">
                        <table class="table align-middle mb-0 rpt-table" id="commTable">
                            <thead>
                                <tr>
                                    <th>Salesperson</th>
                                    <th class="text-center">Month</th>
                                    <th class="text-center">Commission Plan</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Gross Sales</th>
                                    <th class="text-end">Net Sales</th>
                                    <th class="text-center">Rate</th>
                                    <th class="text-end">Commission</th>
                                </tr>
                            </thead>
                            <tbody id="commBody"></tbody>
                            <tfoot id="commFoot"></tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- No data state --}}
            <div id="noDataState" class="text-center py-5 text-muted" style="display:none">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                <div>No completed sales found for the selected period.</div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
var lastParams = {};

function fmt(n) {
    return '৳ ' + Number(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtN(n) {
    return Number(n || 0).toLocaleString('en');
}

var avatarColors = ['#3b82f6','#8b5cf6','#059669','#f59e0b','#ef4444','#06b6d4','#ec4899'];
function avatarColor(name) {
    var code = (name || '').charCodeAt(0) || 0;
    return avatarColors[code % avatarColors.length];
}
function initials(name) {
    return (name || '?').split(' ').slice(0,2).map(function(w){ return (w[0]||'').toUpperCase(); }).join('');
}

$(document).ready(function () {
    $('#generateBtn').on('click', generate);
    $('#resetBtn').on('click', function () {
        $('#yearSel').val('{{ now()->year }}');
        $('#monthSel,#userSel').val('');
        $('#customRate').val('');
        resetUI();
    });
});

function resetUI() {
    $('#summaryCards').hide();
    $('#resultsWrap,#noDataState,#loadingRow').hide();
    $('#emptyState').show();
    $('#excelBtn,#printBtn').hide();
}

function generate() {
    lastParams = {
        year:        $('#yearSel').val(),
        month:       $('#monthSel').val(),
        user_id:     $('#userSel').val(),
        custom_rate: $('#customRate').val(),
        _token:      '{{ csrf_token() }}'
    };

    $('#emptyState,#resultsWrap,#noDataState,#summaryCards').hide();
    $('#loadingRow').show();
    $('#excelBtn,#printBtn').hide();

    $.post('{{ route("report.commission.data") }}', lastParams, function (res) {
        $('#loadingRow').hide();

        if (!res.rows || res.rows.length === 0) {
            $('#noDataState').show();
            return;
        }

        renderSummary(res.summary);
        renderTable(res.rows);

        $('#summaryCards').show();
        $('#resultsWrap').show();
        $('#excelBtn,#printBtn').show();
    }).fail(function () {
        $('#loadingRow').hide();
        $('#noDataState').show();
        toastr.error('Failed to load commission data.');
    });
}

function renderSummary(s) {
    $('#sPersonCount').text(fmtN(s.person_count));
    $('#sOrderCount').text(fmtN(s.order_count));
    $('#sTotalSales').text(fmt(s.total_sales));
    $('#sTotalCommission').text(fmt(s.total_commission));
}

function renderTable(rows) {
    var tbody = '';
    var grandOrders = 0, grandGross = 0, grandNet = 0, grandComm = 0;
    var multiMonth = ($('#monthSel').val() === '');

    rows.forEach(function (person) {
        var color = avatarColor(person.name);
        var ini   = initials(person.name);
        var rowspan = multiMonth ? person.months.length + 1 : 1;

        // Person header row
        tbody += '<tr class="cm-person-row">';
        tbody += '<td rowspan="' + rowspan + '">';
        tbody += '<div style="display:flex;align-items:center;gap:.6rem">';
        tbody += '<div class="cm-avatar" style="background:' + color + '">' + ini + '</div>';
        tbody += '<div><div style="font-size:.88rem;font-weight:800;color:#0f172a">' + esc(person.name) + '</div>';
        tbody += '<div style="font-size:.7rem;color:#94a3b8">' + esc(person.emp_code) + '</div></div></div></td>';

        if (!multiMonth && person.months.length > 0) {
            var m = person.months[0];
            tbody += monthCells(m, person.plan_name, person.plan_type);
        } else {
            tbody += '<td colspan="6" style="font-size:.72rem;color:#64748b;padding:.5rem .75rem">'
                   + '<span class="plan-badge" title="' + esc(person.plan_name) + '">' + esc(person.plan_name) + '</span>'
                   + ' — ' + person.months.length + ' month(s)</td>';
            tbody += '<td class="text-end"><span style="font-weight:800;color:#c2410c">' + fmt(person.total_commission) + '</span></td>';
        }
        tbody += '</tr>';

        // Month detail rows (full year mode)
        if (multiMonth) {
            person.months.forEach(function (m) {
                tbody += '<tr class="cm-month-row">';
                tbody += monthCells(m, person.plan_name, person.plan_type);
                tbody += '</tr>';
            });
            // Person subtotal
            tbody += '<tr>';
            tbody += '<td style="font-size:.72rem;font-weight:800;color:#475569;padding:.45rem .75rem">Subtotal</td>';
            tbody += '<td></td>';
            tbody += '<td class="text-center" style="font-weight:800">' + fmtN(person.total_orders) + '</td>';
            tbody += '<td class="text-end" style="font-weight:800;color:#0f766e">' + fmt(person.total_gross) + '</td>';
            tbody += '<td class="text-end" style="font-weight:800;color:#0f766e">' + fmt(person.total_net) + '</td>';
            tbody += '<td></td>';
            tbody += '<td class="text-end"><span style="font-weight:800;font-size:.9rem;color:#c2410c">' + fmt(person.total_commission) + '</span></td>';
            tbody += '</tr>';
        }

        grandOrders += person.total_orders;
        grandGross  += person.total_gross;
        grandNet    += person.total_net;
        grandComm   += person.total_commission;
    });

    $('#commBody').html(tbody);

    // Grand total footer
    var tfoot = '<tr class="cm-total-row">';
    tfoot += '<td colspan="3" style="font-size:.8rem">Grand Total</td>';
    tfoot += '<td class="text-center">' + fmtN(grandOrders) + '</td>';
    tfoot += '<td class="text-end">' + fmt(grandGross) + '</td>';
    tfoot += '<td class="text-end">' + fmt(grandNet) + '</td>';
    tfoot += '<td></td>';
    tfoot += '<td class="text-end" style="color:#c2410c;font-size:.95rem">' + fmt(grandComm) + '</td>';
    tfoot += '</tr>';
    $('#commFoot').html(tfoot);
}

function monthCells(m, planName, planType) {
    var rateLabel = m.rate > 0
        ? (planType === 'percentage' || $('#customRate').val() !== ''
            ? m.rate + '%' : '৳ ' + m.rate)
        : '—';
    var commColor = m.commission > 0 ? '#c2410c' : '#94a3b8';

    return '<td class="text-center" style="font-size:.8rem;color:#475569">' + esc(m.month_name || 'Single Month') + '</td>'
         + '<td class="text-center"><span class="plan-badge" title="' + esc(planName) + '">' + esc(planName) + '</span></td>'
         + '<td class="text-center" style="font-weight:700">' + fmtN(m.order_count) + '</td>'
         + '<td class="text-end" style="color:#0f766e;font-weight:700">' + fmt(m.gross_sales) + '</td>'
         + '<td class="text-end" style="color:#0f766e;font-weight:700">' + fmt(m.net_sales) + '</td>'
         + '<td class="text-center"><span class="rate-badge">' + rateLabel + '</span></td>'
         + '<td class="text-end"><span style="font-weight:800;color:' + commColor + '">' + fmt(m.commission) + '</span></td>';
}

function esc(str) {
    return $('<div>').text(str || '—').html();
}

function doExcel() {
    var p = $.param(lastParams);
    window.location.href = '{{ route("report.commission.excel") }}?' + p;
}
</script>
@endsection
