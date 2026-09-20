@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
.cust-stat { border-radius:12px;padding:1rem 1.2rem;display:flex;align-items:center;gap:.9rem;border:1px solid transparent;background:#fff; }
.cust-stat .csi { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0; }
.cust-stat .csv { font-size:1.25rem;font-weight:800;line-height:1.1; }
.cust-stat .csl { font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;opacity:.75;margin-top:.15rem; }
.cav { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;font-size:.75rem;font-weight:800;color:#fff;flex-shrink:0; }
#customerTable_wrapper .dataTables_info,
#customerTable_wrapper .dataTables_paginate { padding:.7rem 1.25rem; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Customers</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Customers</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="cust-stat rpt-c1">
                <div class="csi" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="csv" style="color:#1d4ed8">{{ number_format($stats['total']) }}</div>
                    <div class="csl" style="color:#1d4ed8">Total Customers</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cust-stat rpt-c2">
                <div class="csi" style="background:#dcfce7;color:#15803d"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="csv" style="color:#15803d">{{ number_format($stats['active']) }}</div>
                    <div class="csl" style="color:#15803d">Active</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cust-stat rpt-c5">
                <div class="csi" style="background:#fee2e2;color:#dc2626"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div>
                    <div class="csv" style="color:#dc2626">{{ number_format($stats['has_due']) }}</div>
                    <div class="csl" style="color:#dc2626">Has Outstanding Due</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cust-stat rpt-c3">
                <div class="csi" style="background:#ffedd5;color:#c2410c"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="csv" style="color:#c2410c;font-size:1.05rem">৳ {{ number_format($stats['total_due'], 0) }}</div>
                    <div class="csl" style="color:#c2410c">Total Due</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Customer List</div>
                    <div class="rpt-head-sub">Manage customers, view statements and outstanding dues.</div>
                </div>
                <a href="{{ route('customer.create') }}" class="btn btn-primary fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> New Customer
                </a>
            </div>

            {{-- Filters --}}
            <div class="rpt-filter">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-8">
                        <span class="rpt-flabel">Search</span>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Name, phone, email, code...">
                    </div>
                    <div class="col-md-2 col-4">
                        <span class="rpt-flabel">Group</span>
                        <select id="groupFilter" class="form-select form-select-sm">
                            <option value="">All Groups</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->name }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-4">
                        <span class="rpt-flabel">Due Status</span>
                        <select id="dueFilter" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="due">Has Due</option>
                            <option value="clear">No Due</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-4">
                        <span class="rpt-flabel">Status</span>
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6 d-flex gap-1">
                        <button id="applyBtn" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill me-1"></i>Apply</button>
                        <button id="resetBtn" class="btn btn-light btn-sm" style="min-width:36px"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="rpt-scroll">
                    <table id="customerTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Group</th>
                            <th class="text-end">Total Purchase</th>
                            <th class="text-end">Outstanding Due</th>
                            <th class="text-center">Loyalty Pts</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Delete confirm modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div style="font-size:2.5rem;color:#dc2626"><i class="bi bi-trash3-fill"></i></div>
                <div class="fw-bold mt-2 mb-1">Delete Customer?</div>
                <div class="text-muted small mb-3">This action cannot be undone.</div>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger btn-sm px-3 fw-bold" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('footer.js')
<script>
var dt, deleteId = null;

$(document).ready(function () {
    dt = $('#customerTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("customer.list") }}', type: 'POST',
            data: function (d) {
                d._token = '{{ csrf_token() }}';
                d.search_name   = $('#searchInput').val();
                d.group_filter  = $('#groupFilter').val();
                d.due_filter    = $('#dueFilter').val();
                d.status_filter = $('#statusFilter').val();
            }
        },
        columns: [
            {
                data: 'name', name: 'name', orderable: true, searchable: true, width: '190px',
                render: function (d, t, row) {
                    var initials = d.split(' ').slice(0,2).map(function(w){ return w[0]||''; }).join('').toUpperCase();
                    var colors = ['#3b82f6','#8b5cf6','#059669','#f59e0b','#ef4444','#06b6d4'];
                    var color  = colors[initials.charCodeAt(0) % colors.length];
                    return '<div style="display:flex;align-items:center;gap:.6rem">' +
                        '<div class="cav" style="background:'+color+'">'+initials+'</div>' +
                        '<div><div class="rpt-bold" style="font-size:.85rem">'+d+'</div>' +
                        '<div style="font-size:.7rem;color:#94a3b8">'+(row.customer_code||'—')+'</div></div></div>';
                }
            },
            {
                data: 'phone', name: 'phone', orderable: false, searchable: true, width: '140px',
                render: function (d, t, row) {
                    return '<div style="font-size:.82rem;color:#374151">'+(d||'—')+'</div>' +
                        '<div style="font-size:.72rem;color:#94a3b8">'+(row.email||'')+'</div>';
                }
            },
            {
                data: 'group_name', name: 'group_name', orderable: false, searchable: false, width: '110px',
                render: function (d) {
                    return d === 'Ungrouped'
                        ? '<span style="font-size:.75rem;color:#94a3b8">—</span>'
                        : '<span style="background:#e0e7ff;color:#3730a3;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px">'+d+'</span>';
                }
            },
            {
                data: 'total_purchase_amount', name: 'total_purchase_amount', className: 'text-end', orderable: true, searchable: false, width: '120px',
                render: function (d) {
                    return '<span style="font-weight:700;color:#0f766e;font-size:.84rem">৳ '+Number(d||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+'</span>';
                }
            },
            {
                data: 'current_due_display', name: 'current_due', className: 'text-end', orderable: false, searchable: false, width: '120px',
                render: function (d, t, row) {
                    if ((row.raw_due||0) > 0) {
                        return '<span style="font-weight:800;color:#dc2626;font-size:.84rem">'+d+'</span>';
                    }
                    return '<span style="font-weight:700;color:#16a34a;font-size:.82rem"><i class="bi bi-check-circle-fill me-1" style="font-size:.7rem"></i>Clear</span>';
                }
            },
            {
                data: 'loyalty_points', name: 'loyalty_points', className: 'text-center', orderable: true, searchable: false, width: '90px',
                render: function (d) {
                    return '<span style="font-weight:700;color:#7c3aed;font-size:.84rem">'+Number(d||0).toLocaleString()+'</span>';
                }
            },
            { data: 'status_badge', name: 'status', className: 'text-center', orderable: false, searchable: false, width: '80px' },
            {
                data: null, className: 'text-center', orderable: false, searchable: false, width: '110px',
                render: function (d, t, row) {
                    return '<div class="d-flex justify-content-center gap-1">' +
                        '<a href="'+row.statement_url+'" title="View Statement" class="btn btn-sm" style="background:#dbeafe;color:#1d4ed8;border:none;padding:4px 8px"><i class="bi bi-person-lines-fill"></i></a>' +
                        '<a href="'+row.edit_url+'" title="Edit" class="btn btn-sm" style="background:#fef3c7;color:#d97706;border:none;padding:4px 8px"><i class="bi bi-pencil-fill"></i></a>' +
                        '<button onclick="confirmDelete('+row.id+')" title="Delete" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:none;padding:4px 8px"><i class="bi bi-trash3-fill"></i></button>' +
                        '</div>';
                }
            },
        ],
        language: {
            emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-people fs-3 d-block mb-2"></i>No customers found.</div>'
        },
        order: [[0, 'asc']]
    });

    $('#applyBtn').on('click', function () { dt.ajax.reload(); });
    $('#searchInput').on('keydown', function (e) { if (e.key === 'Enter') dt.ajax.reload(); });
    $('#groupFilter, #dueFilter, #statusFilter').on('change', function () { dt.ajax.reload(); });
    $('#resetBtn').on('click', function () {
        $('#searchInput').val('');
        $('#groupFilter, #dueFilter, #statusFilter').val('');
        dt.ajax.reload();
    });
});

function confirmDelete(id) {
    deleteId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

$('#confirmDeleteBtn').on('click', function () {
    $.ajax({
        type: 'POST',
        url: '{{ route("customer.delete") }}',
        data: { _token: '{{ csrf_token() }}', id: deleteId },
        success: function () {
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            toastr.success('Customer deleted successfully!');
            dt.ajax.reload(null, false);
        },
        error: function () {
            toastr.error('Could not delete. Customer may have related records.');
        }
    });
});
</script>
@endsection
