@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Purchase Requisitions</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Purchase Requisitions</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 6px rgba(0,0,0,.05)">
            <div class="card-header d-flex align-items-center justify-content-between" style="border-bottom:1px solid #e2e8f0;padding:.85rem 1.25rem">
                <h5 class="card-title mb-0" style="font-size:.95rem;font-weight:700;color:#1e293b">
                    <i class="bi bi-card-checklist me-2 text-primary"></i>Requisition List
                </h5>
                <a href="{{ route('purchaseRequisition.create') }}" class="btn btn-primary btn-sm fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> New Requisition
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="prTable" class="table table-hover align-middle" style="font-size:.875rem"></table>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Convert to PO Modal --}}
<div class="modal fade" id="convertModal" tabindex="-1" data-bs-backdrop="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:linear-gradient(135deg,#0f62fe,#0a4ecc);border-radius:.375rem .375rem 0 0">
                <h6 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-right-circle-fill"></i> Convert to Purchase Order
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Select the supplier for this Purchase Order.</p>
                <label class="form-label small fw-semibold mb-1">Supplier <span class="text-danger">*</span></label>
                <select id="convertSupplierId" class="form-select form-select-sm">
                    <option value="">— Select Supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}{{ $s->phone ? ' · '.$s->phone : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold" id="confirmConvertBtn">
                    <i class="bi bi-arrow-right-circle-fill me-1"></i>Convert
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" data-bs-backdrop="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">Reject Requisition</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold">Reason for rejection <span class="text-danger">*</span></label>
                <textarea id="rejectionNote" class="form-control form-control-sm" rows="3" placeholder="Enter reason…"></textarea>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm fw-bold" id="confirmRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer.js')
<script>
const PR_ROUTES = {
    list:      "{{ route('purchaseRequisition.list') }}",
    edit:      "{{ route('purchaseRequisition.edit', ':id') }}",
    pdf:       "{{ route('purchaseRequisition.pdf', ':id') }}",
    submit:    "{{ route('purchaseRequisition.submit', ':id') }}",
    approve:   "{{ route('purchaseRequisition.approve', ':id') }}",
    reject:    "{{ route('purchaseRequisition.reject', ':id') }}",
    convert:   "{{ route('purchaseRequisition.convertToPo', ':id') }}",
    delete:    "{{ route('purchaseRequisition.delete') }}",
};
const CSRF = "{{ csrf_token() }}";
let _rejectId = null;
let _convertId = null;

$(document).ready(function () {
    const table = $('#prTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: PR_ROUTES.list, type: 'POST', data: d => { d._token = CSRF; } },
        columns: [
            { title: 'PR No',        data: 'requisition_no',    className: 'fw-semibold' },
            { title: 'Date',         data: 'requisition_date',  className: 'text-muted' },
            { title: 'Branch',       data: 'branch_name',       className: 'text-muted', orderable: false },
            { title: 'Warehouse',    data: 'warehouse_name',    className: 'text-muted', orderable: false },
            { title: 'Items',        data: 'items_count',       className: 'text-center', orderable: false },
            { title: 'Requested By', data: 'requested_by_name', className: 'text-muted', orderable: false },
            { title: 'Status',       data: 'status_badge',      className: 'text-center', orderable: false },
            {
                title: 'Action',
                className: 'text-center',
                orderable: false,
                searchable: false,
                data: function (row) {
                    let btns = '';
                    const editable = ['draft', 'submitted'].includes(row.status);
                    const deletable = ['draft', 'rejected'].includes(row.status);

                    if (editable) {
                        btns += `<button class="btn btn-sm btn-warning fw-semibold px-2" onclick="editPR(${row.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button> `;
                    }
                    if (row.status === 'draft') {
                        btns += `<button class="btn btn-sm btn-primary fw-semibold px-2" onclick="submitPR(${row.id})" title="Submit for Approval"><i class="bi bi-send-fill"></i></button> `;
                    }
                    if (row.status === 'submitted') {
                        btns += `<button class="btn btn-sm btn-success fw-semibold px-2" onclick="approvePR(${row.id})" title="Approve"><i class="bi bi-check-lg"></i></button> `;
                        btns += `<button class="btn btn-sm btn-danger fw-semibold px-2"  onclick="openReject(${row.id})" title="Reject"><i class="bi bi-x-lg"></i></button> `;
                    }
                    if (row.status === 'approved') {
                        btns += `<button class="btn btn-sm btn-info fw-semibold px-2 text-dark" onclick="convertPR(${row.id})" title="Convert to PO"><i class="bi bi-arrow-right-circle-fill"></i></button> `;
                    }
                    btns += `<a href="${PR_ROUTES.pdf.replace(':id', row.id)}" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold px-2" title="Download PDF"><i class="bi bi-file-earmark-pdf-fill"></i></a> `;
                    if (deletable) {
                        btns += `<button class="btn btn-sm btn-outline-danger fw-semibold px-2" onclick="deletePR(${row.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>`;
                    }
                    return `<div class="d-flex gap-1 justify-content-center flex-wrap">${btns}</div>`;
                }
            }
        ]
    });

    window._prTable = table;
});

function editPR(id)    { window.location.href = PR_ROUTES.edit.replace(':id', id); }

function submitPR(id) {
    if (!confirm('Submit this requisition for approval?')) return;
    $.post(PR_ROUTES.submit.replace(':id', id), { _token: CSRF })
        .done(r  => { toastr.success(r.message); window._prTable.ajax.reload(null, false); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
}

function approvePR(id) {
    if (!confirm('Approve this requisition?')) return;
    $.post(PR_ROUTES.approve.replace(':id', id), { _token: CSRF })
        .done(r  => { toastr.success(r.message); window._prTable.ajax.reload(null, false); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
}

function openReject(id) {
    _rejectId = id;
    $('#rejectionNote').val('');
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

document.getElementById('confirmRejectBtn').addEventListener('click', function () {
    const note = $('#rejectionNote').val().trim();
    if (!note) { toastr.warning('Please enter a rejection reason.'); return; }
    $.post(PR_ROUTES.reject.replace(':id', _rejectId), { _token: CSRF, rejection_note: note })
        .done(r  => { toastr.success(r.message); bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide(); window._prTable.ajax.reload(null, false); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
});

function convertPR(id) {
    _convertId = id;
    $('#convertSupplierId').val('');
    new bootstrap.Modal(document.getElementById('convertModal')).show();
    setTimeout(() => document.getElementById('convertSupplierId')?.focus(), 300);
}

document.getElementById('confirmConvertBtn').addEventListener('click', function () {
    const supplierId = $('#convertSupplierId').val();
    if (!supplierId) { toastr.warning('Please select a supplier.'); return; }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Converting…';

    $.post(PR_ROUTES.convert.replace(':id', _convertId), { _token: CSRF, supplier_id: supplierId })
        .done(r => {
            toastr.success(r.message);
            bootstrap.Modal.getInstance(document.getElementById('convertModal')).hide();
            setTimeout(() => window.location.href = r.redirect, 600);
        })
        .fail(xhr => {
            toastr.error(xhr.responseJSON?.message || 'Error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-1"></i>Convert';
        });
});

function deletePR(id) {
    if (!confirm('Delete this requisition?')) return;
    $.post(PR_ROUTES.delete, { _token: CSRF, id: id })
        .done(r  => { toastr.success(r.success); window._prTable.ajax.reload(null, false); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Unable to delete.'));
}
</script>
@endsection
