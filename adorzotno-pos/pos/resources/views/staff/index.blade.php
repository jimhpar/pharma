@extends('layouts.main')
@section('main.content')
@include('report.partials._report-styles')
<style>
.modal.fade .modal-dialog { transition:none!important; }
#commModal { background:rgba(15,23,42,.35)!important; }
.st-avatar { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;font-size:.72rem;font-weight:800;color:#fff;flex-shrink:0; }
.rate-pill { display:inline-flex;align-items:center;gap:.3rem;background:#eff6ff;color:#1d4ed8;font-size:.72rem;font-weight:800;border-radius:20px;padding:3px 10px;cursor:pointer;border:1.5px solid #bfdbfe;transition:all .15s; }
.rate-pill:hover { background:#dbeafe;border-color:#93c5fd; }
.rate-pill.no-plan { background:#f8fafc;color:#94a3b8;border-color:#e2e8f0; }
.plan-card { border:2px solid transparent;border-radius:10px;padding:.75rem 1rem;cursor:pointer;transition:all .15s;margin-bottom:.5rem; }
.plan-card:hover { border-color:#bfdbfe;background:#f0f7ff; }
.plan-card.selected { border-color:#3b82f6;background:#eff6ff; }
.plan-card.no-plan-card { border-style:dashed;border-color:#e2e8f0; }
.plan-card.no-plan-card.selected { border-color:#94a3b8;background:#f8fafc; }
#staffTable_wrapper .dataTables_info,
#staffTable_wrapper .dataTables_paginate { padding:.7rem 1.25rem; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Staff Management</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Staff</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card rpt-card">
            <div class="rpt-head">
                <div>
                    <div class="rpt-head-title">Staff List</div>
                    <div class="rpt-head-sub">
                        Manage salespersons and set commission rates.
                        <a href="{{ route('salesCommissionPlan.show') }}" class="ms-1 text-primary" style="font-size:.75rem">
                            <i class="bi bi-gear-fill me-1"></i>Manage Commission Plans
                        </a>
                    </div>
                </div>
                <a href="{{ route('staff.create') }}" class="btn btn-primary fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> New Staff
                </a>
            </div>

            <div class="card-body p-0">
                <div class="rpt-scroll">
                    <table id="staffTable" class="table align-middle mb-0 rpt-table">
                        <thead><tr>
                            <th>Staff</th>
                            <th>Contact</th>
                            <th>Branch / Role</th>
                            <th class="text-center">Commission Rate</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Commission Quick-Set Modal --}}
<div class="modal fade" id="commModal" tabindex="-1" aria-labelledby="commModalLabel" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 20px 60px rgba(15,23,42,.18)">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e293b,#0f172a);border-radius:14px 14px 0 0;border:none;padding:1.1rem 1.4rem">
                <div>
                    <h5 class="modal-title text-white mb-0" id="commModalLabel">Set Commission Rate</h5>
                    <div class="text-white" style="font-size:.72rem;opacity:.6;margin-top:.15rem" id="commModalSubtitle"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.4rem">
                <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.75rem">
                    Select Commission Plan
                </div>

                <input type="hidden" id="commStaffId">
                <div id="planList">
                    {{-- No Plan option --}}
                    <div class="plan-card no-plan-card" data-plan-id="0" onclick="selectPlan(this)">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:36px;height:36px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.85rem;color:#64748b">No Commission Plan</div>
                                <div style="font-size:.72rem;color:#94a3b8">This person won't earn commission</div>
                            </div>
                        </div>
                    </div>

                    @foreach($commissionPlans as $plan)
                    @php
                        $rateLabel = $plan->calculation_type === 'percentage'
                            ? number_format((float)$plan->rate, 2) . '% of ' . str_replace('_', ' ', $plan->base_amount_type)
                            : '৳' . number_format((float)$plan->rate, 2) . ' fixed per invoice';
                        $colors = ['#3b82f6','#8b5cf6','#059669','#f59e0b','#ef4444'];
                        $ci = $loop->index % count($colors);
                        $color = $colors[$ci];
                        $bgs = ['#dbeafe','#f3e8ff','#dcfce7','#fef3c7','#fee2e2'];
                        $bg = $bgs[$ci];
                    @endphp
                    <div class="plan-card" data-plan-id="{{ $plan->id }}" onclick="selectPlan(this)">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:36px;height:36px;border-radius:8px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;color:{{ $color }};font-size:.95rem;font-weight:800;flex-shrink:0">
                                {{ $plan->calculation_type === 'percentage' ? '%' : '৳' }}
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:800;font-size:.88rem;color:#0f172a">{{ $plan->name }}</div>
                                <div style="font-size:.72rem;color:#64748b">{{ $rateLabel }}</div>
                                @if($plan->min_target_amount)
                                    <div style="font-size:.66rem;color:#94a3b8">Min target: ৳{{ number_format((float)$plan->min_target_amount, 0) }}</div>
                                @endif
                            </div>
                            <div style="font-size:1.2rem;font-weight:800;color:{{ $color }}">
                                {{ $plan->calculation_type === 'percentage' ? number_format((float)$plan->rate, 1).'%' : '৳'.number_format((float)$plan->rate,0) }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($commissionPlans->isEmpty())
                <div class="text-center py-3 text-muted small">
                    <i class="bi bi-info-circle d-block mb-1 fs-4"></i>
                    No commission plans found.
                    <a href="{{ route('salesCommissionPlan.create') }}" class="text-primary d-block mt-1">Create a plan first</a>
                </div>
                @endif
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:.85rem 1.4rem">
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" id="saveCommBtn">
                    <i class="bi bi-check2 me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete confirm modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div style="font-size:2.5rem;color:#dc2626"><i class="bi bi-trash3-fill"></i></div>
                <div class="fw-bold mt-2 mb-1">Delete Staff Profile?</div>
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
var deleteId = null, selectedPlanId = null;
var avatarColors = ['#3b82f6','#8b5cf6','#059669','#f59e0b','#ef4444','#06b6d4'];

function avatarColor(n){ return avatarColors[(n||'?').charCodeAt(0) % avatarColors.length]; }
function initials(n){ return (n||'?').split(' ').slice(0,2).map(function(w){ return (w[0]||'').toUpperCase(); }).join(''); }

$(document).ready(function () {
    $('#staffTable').DataTable({
        processing: true, serverSide: true, autoWidth: false, stateSave: false,
        dom: 'rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: '{{ route("staff.list") }}', type: 'POST',
            data: function (d) { d._token = '{{ csrf_token() }}'; }
        },
        columns: [
            {
                data: 'staff_name', name: 'staff_name', width: '180px',
                render: function (d, t, row) {
                    var col = avatarColor(d), ini = initials(d);
                    return '<div style="display:flex;align-items:center;gap:.6rem">'
                         + '<div class="st-avatar" style="background:'+col+'">'+ini+'</div>'
                         + '<div><div class="rpt-bold" style="font-size:.85rem">'+d+'</div>'
                         + '<div style="font-size:.7rem;color:#94a3b8">'+(row.employee_code||'—')+'</div></div></div>';
                }
            },
            {
                data: 'email', name: 'email', width: '160px',
                render: function (d, t, row) {
                    return '<div style="font-size:.82rem">'+(d||'—')+'</div>'
                         + '<div style="font-size:.72rem;color:#94a3b8">'+(row.phone||'')+'</div>';
                }
            },
            {
                data: 'default_branch', name: 'default_branch', width: '160px',
                render: function (d, t, row) {
                    var branch = d !== 'N/A' ? '<span style="font-size:.8rem;font-weight:600;color:#374151">'+d+'</span>' : '<span class="text-muted small">—</span>';
                    var roles = row.role_summary ? '<div style="font-size:.7rem;color:#64748b;margin-top:.1rem">'+row.role_summary.replace(/<br>/g,', ')+'</div>' : '';
                    return branch + roles;
                }
            },
            {
                data: 'commission_rate', name: 'commission_rate', className: 'text-center', width: '150px',
                render: function (d, t, row) {
                    var hasPlan = row.commission_plan_id > 0;
                    var planName = hasPlan ? row.commission_plan : 'No Plan';
                    var rateLabel = hasPlan ? d : 'Set Rate';
                    return '<span class="rate-pill '+(hasPlan?'':'no-plan')+'" onclick="openCommModal('+row.id+','+row.commission_plan_id+',\''+esc(row.staff_name)+'\')" title="Click to change commission plan">'
                         + '<i class="bi bi-'+(hasPlan?'percent':'plus-circle')+'" style="font-size:.65rem"></i>'
                         + '<span>'+rateLabel+'</span>'
                         + '</span>'
                         + (hasPlan ? '<div style="font-size:.66rem;color:#64748b;margin-top:.2rem">'+esc(planName)+'</div>' : '');
                }
            },
            {
                data: 'salesperson_badge', name: 'salesperson_badge', className: 'text-center', width: '80px',
                render: function (d) {
                    var isSales = d.indexOf('Sales') !== -1;
                    return isSales
                        ? '<span style="background:#dbeafe;color:#1e40af;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px">Sales</span>'
                        : '<span style="background:#f1f5f9;color:#64748b;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px">Support</span>';
                }
            },
            {
                data: 'status_badge', name: 'status_badge', className: 'text-center', width: '80px',
                render: function (d) {
                    var isActive = d.indexOf('Active') !== -1 && d.indexOf('Inactive') === -1;
                    return isActive
                        ? '<span style="background:#d1fae5;color:#065f46;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px">Active</span>'
                        : '<span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px">Inactive</span>';
                }
            },
            {
                data: null, className: 'text-center', orderable: false, width: '90px',
                render: function (d, t, row) {
                    var editUrl = '{{ route("staff.edit", ":id") }}'.replace(':id', row.id);
                    return '<div class="d-flex justify-content-center gap-1">'
                         + '<a href="'+editUrl+'" title="Edit" class="btn btn-sm" style="background:#fef3c7;color:#d97706;border:none;padding:4px 8px"><i class="bi bi-pencil-fill"></i></a>'
                         + '<button onclick="confirmDelete('+row.id+')" title="Delete" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:none;padding:4px 8px"><i class="bi bi-trash3-fill"></i></button>'
                         + '</div>';
                }
            },
        ],
        language: {
            emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-people fs-3 d-block mb-2"></i>No staff profiles found.</div>'
        },
        order: [[0, 'asc']]
    });
});

function esc(s){ return String(s||'').replace(/'/g,"\\'"); }

// ── Commission modal ────────────────────────────────────
function openCommModal(staffId, planId, staffName) {
    $('#commStaffId').val(staffId);
    $('#commModalSubtitle').text(staffName);
    selectedPlanId = planId || 0;

    // Mark selected
    $('.plan-card').removeClass('selected');
    $('.plan-card[data-plan-id="'+selectedPlanId+'"]').addClass('selected');

    new bootstrap.Modal(document.getElementById('commModal')).show();
}

function selectPlan(el) {
    $('.plan-card').removeClass('selected');
    $(el).addClass('selected');
    selectedPlanId = $(el).data('plan-id');
}

$('#saveCommBtn').on('click', function () {
    var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    $.post('{{ route("staff.updateCommission") }}', {
        _token:            '{{ csrf_token() }}',
        staff_id:          $('#commStaffId').val(),
        commission_plan_id: selectedPlanId || '',
    }, function (res) {
        bootstrap.Modal.getInstance(document.getElementById('commModal')).hide();
        toastr.success('Commission rate updated!');
        $('#staffTable').DataTable().ajax.reload(null, false);
    }).fail(function () {
        toastr.error('Failed to update commission.');
    }).always(function () {
        $('#saveCommBtn').prop('disabled', false).html('<i class="bi bi-check2 me-1"></i> Save');
    });
});

// ── Delete ──────────────────────────────────────────────
function confirmDelete(id) {
    deleteId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

$('#confirmDeleteBtn').on('click', function () {
    $.ajax({
        type: 'POST',
        url: '{{ route("staff.delete") }}',
        data: { _token: '{{ csrf_token() }}', id: deleteId },
        success: function () {
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            toastr.success('Staff profile deleted!');
            $('#staffTable').DataTable().ajax.reload(null, false);
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Unable to delete staff profile.');
        }
    });
});
</script>
@endsection
