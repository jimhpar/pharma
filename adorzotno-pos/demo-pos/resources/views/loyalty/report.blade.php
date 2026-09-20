@extends('layouts.main')
@php use App\Support\Currency; @endphp
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Loyalty Reports</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('loyalty.settings') }}">Loyalty</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small">Active Points</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format($summary['total_points_in_circulation']) }}</div>
                        <div class="text-muted small">in circulation</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Earned</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($summary['total_lifetime_earned']) }}</div>
                        <div class="text-muted small">all time</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Redeemed</div>
                        <div class="fs-4 fw-bold text-warning">{{ number_format($summary['total_lifetime_redeemed']) }}</div>
                        <div class="text-muted small">all time</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small">Discount Given</div>
                        <div class="fs-4 fw-bold text-danger">{{ Currency::format($summary['total_point_discount_given']) }}</div>
                        <div class="text-muted small">from points</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small">Members</div>
                        <div class="fs-4 fw-bold text-info">{{ number_format($summary['total_member_customers']) }}</div>
                        <div class="text-muted small">customers</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small">With Points</div>
                        <div class="fs-4 fw-bold text-secondary">{{ number_format($summary['total_customers_with_points']) }}</div>
                        <div class="text-muted small">customers</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-3" id="reportTabs">
            <li class="nav-item">
                <button class="nav-link active" data-tab="customers">Customer Balances</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-tab="ledger">Ledger History</button>
            </li>
        </ul>

        {{-- Customer Balances Panel --}}
        <div id="panel-customers">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="card-title mb-0">Customer Point Balances</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="customerSearch" class="form-control form-control-sm" placeholder="Search name/phone/code..." style="width:200px">
                        <div class="form-check form-check-inline mb-0 ms-2">
                            <input class="form-check-input" type="checkbox" id="membersOnly">
                            <label class="form-check-label small" for="membersOnly">Members only</label>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" id="refreshCustomers">
                            <i class="fas fa-sync"></i>
                        </button>
                        <button class="btn btn-sm btn-success" id="downloadCustomersExcel">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="customersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th class="text-end">Points</th>
                                    <th class="text-end">Lifetime Earned</th>
                                    <th class="text-end">Lifetime Redeemed</th>
                                    <th class="text-end">Total Purchase</th>
                                    <th class="text-center">Status</th>
                                    <th>Member Since</th>
                                </tr>
                            </thead>
                            <tbody id="customersBody">
                                <tr><td colspan="9" class="text-center py-4 text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="customersPager" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small text-muted"></div>
                </div>
            </div>
        </div>

        {{-- Ledger Panel --}}
        <div id="panel-ledger" class="d-none">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="card-title mb-0">Point Transaction Ledger</h5>
                    <button class="btn btn-sm btn-outline-primary" id="refreshLedger">
                        <i class="fas fa-sync"></i>
                    </button>
                    <button class="btn btn-sm btn-success" id="downloadLedgerExcel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-end">Points</th>
                                    <th class="text-end">Value</th>
                                    <th>Order</th>
                                    <th>Note</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerBody">
                                <tr><td colspan="8" class="text-center py-4 text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="ledgerPager" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small text-muted"></div>
                </div>
            </div>
        </div>

    </section>
</div>
@endsection
@section('footer.js')
<script>
    const listUrl   = '{{ route("loyalty.report.list") }}';
    let customersPage = 1;
    let ledgerPage    = 1;

    // Tab switching
    document.querySelectorAll('[data-tab]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const tab = this.dataset.tab;
            document.getElementById('panel-customers').classList.toggle('d-none', tab !== 'customers');
            document.getElementById('panel-ledger').classList.toggle('d-none', tab !== 'ledger');
            if (tab === 'ledger') loadLedger();
        });
    });

    function loadCustomers(page = 1) {
        customersPage = page;
        const search  = document.getElementById('customerSearch').value;
        const members = document.getElementById('membersOnly').checked ? 1 : 0;
        fetch(`${listUrl}?report_type=customers&search=${encodeURIComponent(search)}&members_only=${members}&page=${page}`)
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('customersBody');
                if (!data.data.length) {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No records found.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.data.map(r => `
                    <tr>
                        <td><code>${r.code || '—'}</code></td>
                        <td class="fw-semibold">${r.name}</td>
                        <td>${r.phone || '—'}</td>
                        <td class="text-end fw-bold text-primary">${r.loyalty_points}</td>
                        <td class="text-end text-success">${r.lifetime_earned}</td>
                        <td class="text-end text-warning">${r.lifetime_redeemed}</td>
                        <td class="text-end">${r.total_purchase}</td>
                        <td class="text-center">${r.member_badge}</td>
                        <td class="small">${r.member_since}</td>
                    </tr>`).join('');
                document.getElementById('customersPager').innerHTML =
                    `Showing page ${data.meta.current_page} of ${data.meta.last_page} (${data.meta.total} total) ` +
                    (page > 1 ? `<button class="btn btn-xs btn-outline-secondary me-1" onclick="loadCustomers(${page-1})">Prev</button>` : '') +
                    (page < data.meta.last_page ? `<button class="btn btn-xs btn-outline-secondary" onclick="loadCustomers(${page+1})">Next</button>` : '');
            });
    }

    function loadLedger(page = 1) {
        ledgerPage = page;
        fetch(`${listUrl}?report_type=ledger&page=${page}`)
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('ledgerBody');
                if (!data.data.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No records found.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.data.map(r => `
                    <tr>
                        <td class="small">${r.date}</td>
                        <td>${r.customer}</td>
                        <td class="text-center">${r.type_badge}</td>
                        <td class="text-end fw-bold ${r.points.startsWith('-') ? 'text-danger' : 'text-success'}">${r.points}</td>
                        <td class="text-end">${r.amount_value}</td>
                        <td class="small">${r.order_no}</td>
                        <td class="small text-muted">${r.note || '—'}</td>
                        <td class="small">${r.created_by}</td>
                    </tr>`).join('');
                document.getElementById('ledgerPager').innerHTML =
                    `Showing page ${data.meta.current_page} of ${data.meta.last_page} (${data.meta.total} total) ` +
                    (page > 1 ? `<button class="btn btn-xs btn-outline-secondary me-1" onclick="loadLedger(${page-1})">Prev</button>` : '') +
                    (page < data.meta.last_page ? `<button class="btn btn-xs btn-outline-secondary" onclick="loadLedger(${page+1})">Next</button>` : '');
            });
    }

    document.getElementById('customerSearch').addEventListener('input', () => loadCustomers(1));
    document.getElementById('membersOnly').addEventListener('change', () => loadCustomers(1));
    document.getElementById('refreshCustomers').addEventListener('click', () => loadCustomers(customersPage));
    document.getElementById('refreshLedger').addEventListener('click', () => loadLedger(ledgerPage));
    document.getElementById('downloadCustomersExcel').addEventListener('click', () => {
        const params = new URLSearchParams({
            report_type: 'customers',
            search: document.getElementById('customerSearch').value || '',
            members_only: document.getElementById('membersOnly').checked ? 1 : 0
        });
        window.location.href = "{{ route('loyalty.report.excel') }}?" + params.toString();
    });
    document.getElementById('downloadLedgerExcel').addEventListener('click', () => {
        const params = new URLSearchParams({ report_type: 'ledger' });
        window.location.href = "{{ route('loyalty.report.excel') }}?" + params.toString();
    });

    // Initial load
    loadCustomers(1);
</script>
@endsection
