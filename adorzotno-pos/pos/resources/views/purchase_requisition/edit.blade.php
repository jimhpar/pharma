@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Purchase Requisition — {{ $pr->requisition_no }}</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchaseRequisition.show') }}">Purchase Requisitions</a></li>
                        <li class="breadcrumb-item active">{{ $pr->requisition_no }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <form method="POST" action="{{ route('purchaseRequisition.update', $pr->id) }}">
            @csrf
            <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 6px rgba(0,0,0,.05)">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom:1px solid #e2e8f0;padding:.85rem 1.25rem">
                    <h5 class="card-title mb-0" style="font-size:.95rem;font-weight:700;color:#1e293b">
                        <i class="bi bi-card-checklist me-2 text-primary"></i>
                        {{ $pr->requisition_no }}
                        @php
                            $badgeMap = ['draft'=>'secondary','submitted'=>'primary','approved'=>'success','rejected'=>'danger','converted'=>'info'];
                        @endphp
                        <span class="badge bg-{{ $badgeMap[$pr->status] ?? 'secondary' }} ms-2" style="font-size:.72rem">{{ ucfirst($pr->status) }}</span>
                    </h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('purchaseRequisition.show') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                        <a href="{{ route('purchaseRequisition.pdf', $pr->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm fw-bold">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                        </a>

                        @if($pr->status === 'draft')
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <i class="bi bi-save me-1"></i>Save
                            </button>
                            <button type="button" class="btn btn-success btn-sm fw-bold" id="submitPrBtn">
                                <i class="bi bi-send-fill me-1"></i>Submit for Approval
                            </button>
                        @elseif($pr->status === 'submitted')
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <i class="bi bi-save me-1"></i>Save
                            </button>
                            <button type="button" class="btn btn-success btn-sm fw-bold" id="approvePrBtn">
                                <i class="bi bi-check-lg me-1"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-lg me-1"></i>Reject
                            </button>
                        @elseif($pr->status === 'approved')
                                <button type="button" class="btn btn-info btn-sm fw-bold text-dark" id="convertPrBtn">
                                <i class="bi bi-arrow-right-circle-fill me-1"></i>Convert to PO
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @include('purchase_requisition._form')
                </div>
            </div>
        </form>
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
const PR_ID   = {{ $pr->id }};
const CSRF    = "{{ csrf_token() }}";
const SUBMIT_URL  = "{{ route('purchaseRequisition.submit',  $pr->id) }}";
const APPROVE_URL = "{{ route('purchaseRequisition.approve', $pr->id) }}";
const REJECT_URL  = "{{ route('purchaseRequisition.reject',  $pr->id) }}";
const CONVERT_URL = "{{ route('purchaseRequisition.convertToPo', $pr->id) }}";

document.getElementById('submitPrBtn')?.addEventListener('click', function () {
    if (!confirm('Submit this requisition for approval?')) return;
    $.post(SUBMIT_URL, { _token: CSRF })
        .done(r  => { toastr.success(r.message); setTimeout(() => location.reload(), 800); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
});

document.getElementById('approvePrBtn')?.addEventListener('click', function () {
    if (!confirm('Approve this requisition?')) return;
    $.post(APPROVE_URL, { _token: CSRF })
        .done(r  => { toastr.success(r.message); setTimeout(() => location.reload(), 800); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
});

document.getElementById('confirmRejectBtn')?.addEventListener('click', function () {
    const note = $('#rejectionNote').val().trim();
    if (!note) { toastr.warning('Please enter a rejection reason.'); return; }
    $.post(REJECT_URL, { _token: CSRF, rejection_note: note })
        .done(r  => { toastr.success(r.message); setTimeout(() => location.reload(), 800); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
});

document.getElementById('convertPrBtn')?.addEventListener('click', function () {
    $('#convertSupplierId').val('');
    new bootstrap.Modal(document.getElementById('convertModal')).show();
    setTimeout(() => document.getElementById('convertSupplierId')?.focus(), 300);
});

document.getElementById('confirmConvertBtn')?.addEventListener('click', function () {
    const supplierId = $('#convertSupplierId').val();
    if (!supplierId) { toastr.warning('Please select a supplier.'); return; }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Converting…';

    $.post(CONVERT_URL, { _token: CSRF, supplier_id: supplierId })
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
</script>
@endsection
