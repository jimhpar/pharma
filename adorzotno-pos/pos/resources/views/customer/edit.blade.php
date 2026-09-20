@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Customer Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.show') }}">Customers</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Customer Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="customerEditTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tabProfile">Profile</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tabPrescriptions">
                Prescriptions <span class="badge bg-secondary ms-1" id="rxBadge">0</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Profile Tab -->
        <div class="tab-pane fade show active" id="tabProfile">
            <section id="multiple-column-form">
                <div class="row match-height">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Customer Edit Form</h4>
                            </div>
                            <div class="card-content">
                                <div class="card-body">
                                    <form class="form" method="post" action="{{ route('customer.update', $customer->id) }}">
                                        @csrf
                                        @include('customer._form')
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Prescriptions Tab -->
        <div class="tab-pane fade" id="tabPrescriptions">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Prescriptions</h4>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#addRxForm">
                        <i class="fas fa-plus me-1"></i>Add New
                    </button>
                </div>
                <div class="card-body">
                    <!-- Add Form -->
                    <div class="collapse mb-4" id="addRxForm">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="mb-2">
                                <label class="form-label small">Title (optional)</label>
                                <input type="text" class="form-control form-control-sm" id="profileRxTitle" placeholder="e.g. Eye checkup">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea class="form-control form-control-sm" id="profileRxNotes" rows="4" placeholder="Doctor's notes, medicine list..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Prescription Image (optional)</label>
                                <input type="file" class="form-control form-control-sm" id="profileRxImage" accept="image/*">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="profileSaveRxBtn">
                                <i class="fas fa-save me-1"></i>Save Prescription
                            </button>
                        </div>
                    </div>

                    <!-- List -->
                    <div id="profileRxList">
                        <div class="text-center text-muted py-4">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('footer.js')
<script>
(function() {
    const customerId = {{ $customer->id }};
    const prescriptionUrl = '{{ url("customer") }}/' + customerId + '/prescriptions';

    function loadProfilePrescriptions() {
        $.getJSON(prescriptionUrl, function(data) {
            document.getElementById('rxBadge').textContent = data.length;
            const list = document.getElementById('profileRxList');
            if (!data.length) {
                list.innerHTML = '<div class="text-center text-muted py-4">No prescriptions saved yet.</div>';
                return;
            }
            list.innerHTML = `<div class="row g-3">${data.map(rx => `
                <div class="col-12 col-md-6" id="profileRx-${rx.id}">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="fw-semibold">${rx.title || 'Prescription #' + rx.id}</div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">${rx.created_at}</small>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 delete-profile-rx" data-id="${rx.id}">Delete</button>
                            </div>
                        </div>
                        ${rx.notes ? `<p class="small text-muted mb-2" style="white-space:pre-wrap;">${rx.notes}</p>` : ''}
                        ${rx.image_url ? `<a href="${rx.image_url}" target="_blank"><img src="${rx.image_url}" class="img-fluid rounded" style="max-height:180px;" alt="Prescription"></a>` : ''}
                    </div>
                </div>
            `).join('')}</div>`;
        });
    }

    loadProfilePrescriptions();

    document.getElementById('profileSaveRxBtn').addEventListener('click', function() {
        const title = document.getElementById('profileRxTitle').value.trim();
        const notes = document.getElementById('profileRxNotes').value.trim();
        const imageFile = document.getElementById('profileRxImage').files[0];

        if (!notes && !imageFile) {
            toastr.warning('Please add notes or an image.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (title) formData.append('title', title);
        if (notes) formData.append('notes', notes);
        if (imageFile) formData.append('image', imageFile);

        const btn = this;
        btn.disabled = true;

        $.ajax({
            url: prescriptionUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                toastr.success('Prescription saved!');
                document.getElementById('profileRxTitle').value = '';
                document.getElementById('profileRxNotes').value = '';
                document.getElementById('profileRxImage').value = '';
                bootstrap.Collapse.getInstance(document.getElementById('addRxForm'))?.hide();
                loadProfilePrescriptions();
            },
            error: function() { toastr.error('Failed to save.'); },
            complete: function() { btn.disabled = false; }
        });
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-profile-rx');
        if (!btn) return;
        if (!confirm('Delete this prescription?')) return;
        const rxId = btn.dataset.id;
        $.ajax({
            url: prescriptionUrl + '/' + rxId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                document.getElementById('profileRx-' + rxId)?.remove();
                toastr.success('Deleted.');
                loadProfilePrescriptions();
            },
            error: function() { toastr.error('Failed to delete.'); }
        });
    });
})();
</script>
@endsection
@endsection
