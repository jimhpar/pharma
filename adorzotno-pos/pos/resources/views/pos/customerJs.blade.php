<script>
    const searchInput = document.getElementById('customerSearch');
    const suggestionsDropdown = document.getElementById('suggestionsDropdown');
    const createCustomerForm = document.getElementById('createCustomerForm');
    const customerNameInput = document.getElementById('customerName');
    const customerPhoneInput = document.getElementById('customerPhone');
    const customerEmailInput = document.getElementById('customerEmail');
    const customerBillingAddressInput = document.getElementById('customerBillingAddress');
    const customerShippingAddressInput = document.getElementById('customerShippingAddress');
    const customerShipmentZoneSelect = document.getElementById('customerShipmentZone');
    const customerIsMemberInput = document.getElementById('customerIsMember');
    const orderShipmentZoneSelect = document.getElementById('shipment_zone_id');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('saveBtn');
    const walkInCustomerBtn = document.getElementById('walkInCustomerBtn');
    const customerSearchModal = document.getElementById('customerSearchModal');

    let selectedCustomer = null;
    let lastResults = [];

    function syncShipmentZoneSelection(shipmentZoneId = '', deliveryCharge = null) {
        const zoneId = shipmentZoneId ? String(shipmentZoneId) : '';
        orderShipmentZoneSelect.value = zoneId;

        if (typeof window.setDeliveryChargeFromZone === 'function') {
            window.setDeliveryChargeFromZone(zoneId, deliveryCharge);
        }
    }

    function closeCustomerSearchModal() {
        if (!customerSearchModal) return;

        // Hide the modal element immediately
        customerSearchModal.classList.remove('show');
        customerSearchModal.style.display = 'none';
        customerSearchModal.setAttribute('aria-hidden', 'true');
        customerSearchModal.removeAttribute('aria-modal');

        // Remove any stray backdrops just in case
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        // Let Bootstrap update its internal state
        if (typeof bootstrap !== 'undefined') {
            try {
                const instance = bootstrap.Modal.getInstance(customerSearchModal);
                if (instance) instance.hide();
            } catch (e) {}
        }
    }

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        if (!query) {
            suggestionsDropdown.innerHTML = '';
            suggestionsDropdown.classList.remove('show');
            return;
        }

        $.ajax({
            url: "{{ route('posCustomer.customerSearch') }}",
            type: 'GET',
            data: { query: query },
            success: function(customers) {
                lastResults = customers;
                renderSuggestions(customers, query);
            },
            error: function() {
                suggestionsDropdown.innerHTML = '';
                suggestionsDropdown.classList.remove('show');
            }
        });
    });

    function renderSuggestions(customers, query) {
        let html = '';

        customers.forEach(customer => {
            html += `
                <div class="suggestion-item" data-customer-id="${customer.id}">
                    <div class="suggestion-name">${customer.name}</div>
                    <div class="suggestion-phone">${customer.phone}</div>
                </div>
            `;
        });

        if (query.trim()) {
            html += `
                <div class="suggestion-item create-option" data-action="create">
                    <div class="suggestion-name">+ Create customer with "${query}"</div>
                    <div class="suggestion-phone">Use phone number to add quickly</div>
                </div>
            `;
        }

        suggestionsDropdown.innerHTML = html;
        suggestionsDropdown.classList.add('show');

        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', handleSuggestionClick);
        });
    }

    function handleSuggestionClick(e) {
        const item = e.currentTarget;
        const customerId = item.dataset.customerId;
        const action = item.dataset.action;

        if (action === 'create') {
            showCreateForm(searchInput.value.trim());
        } else if (customerId) {
            const customer = lastResults.find(c => c.id == customerId);
            if (customer) {
                selectCustomer(customer);
            }
        }
    }

    function showCustomerExtraPanel(customerId, customerNote) {
        const panel = document.getElementById('customerExtraPanel');
        if (!panel) return;
        panel.classList.remove('d-none');

        const noteField = document.getElementById('customerNoteField');
        const saveBtn   = document.getElementById('saveCustomerNoteBtn');
        if (noteField) {
            noteField.value = customerNote || '';
            noteField._originalValue = customerNote || '';
        }
        if (saveBtn) saveBtn.style.display = 'none';

        if (customerId) loadPrescriptionCount(customerId);
    }

    function hideCustomerExtraPanel() {
        const panel = document.getElementById('customerExtraPanel');
        if (panel) panel.classList.add('d-none');
        const rxCount = document.getElementById('rxCount');
        if (rxCount) rxCount.textContent = '0';
        const noteField = document.getElementById('customerNoteField');
        if (noteField) { noteField.value = ''; noteField._originalValue = ''; }
        const saveBtn = document.getElementById('saveCustomerNoteBtn');
        if (saveBtn) saveBtn.style.display = 'none';
    }

    // Show Save button when note changes
    document.getElementById('customerNoteField')?.addEventListener('input', function() {
        const saveBtn = document.getElementById('saveCustomerNoteBtn');
        if (!saveBtn) return;
        saveBtn.style.display = this.value !== (this._originalValue || '') ? 'inline-block' : 'none';
    });

    // Save customer note
    document.getElementById('saveCustomerNoteBtn')?.addEventListener('click', function() {
        if (!selectedCustomer?.id) return;
        const noteField = document.getElementById('customerNoteField');
        const note = noteField?.value || '';
        const btn = this;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        $.post('{{ route("posCustomer.updateNote", ["id" => "__ID__"]) }}'.replace('__ID__', selectedCustomer.id), {
            _token: '{{ csrf_token() }}',
            note: note
        }).done(function() {
            toastr.success('Customer note saved.');
            if (noteField) noteField._originalValue = note;
            btn.style.display = 'none';
        }).fail(function() {
            toastr.error('Failed to save note.');
        }).always(function() {
            btn.disabled = false;
            btn.textContent = 'Save';
        });
    });

    function loadPrescriptionCount(customerId) {
        $.getJSON('{{ url("customer") }}/' + customerId + '/prescriptions', function(data) {
            const rxCount = document.getElementById('rxCount');
            if (rxCount) rxCount.textContent = data.length;
        });
    }

    function selectCustomer(customer) {
        selectedCustomer = customer;
        searchInput.value = customer.name;
        suggestionsDropdown.classList.remove('show');
        createCustomerForm.classList.remove('show');
        document.getElementById('customer_id').value = customer.id;

        syncShipmentZoneSelection(customer.shipment_zone_id, customer.shipment_zone_charge);

        if (typeof window.onCustomerSelected === 'function') {
            window.onCustomerSelected(customer.id);
        }

        document.getElementById('selectedCustomer').innerHTML = `
            <div class="selected-customer-card">
                <div>
                    <div class="selected-customer-name">${customer.name}${customer.is_member ? ' <span class="badge bg-success ms-1">Member</span>' : ''}</div>
                    <div class="selected-customer-meta">
                        ${customer.phone || 'No phone'} | Zone: ${customer.shipment_zone_name || 'Not selected'}
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="clearCustomer">Change</button>
            </div>
        `;

        showCustomerExtraPanel(customer.id, customer.note);
        closeCustomerSearchModal();
    }

    function selectWalkInCustomer() {
        selectedCustomer = {
            id: null,
            name: 'Walk-in Customer',
        };

        searchInput.value = 'Walk-in Customer';
        suggestionsDropdown.classList.remove('show');
        createCustomerForm.classList.remove('show');
        document.getElementById('customer_id').value = '';
        syncShipmentZoneSelection('', 0);

        if (typeof window.onCustomerCleared === 'function') {
            window.onCustomerCleared();
        }

        document.getElementById('selectedCustomer').innerHTML = `
            <div class="selected-customer-card">
                <div>
                    <div class="selected-customer-name">Walk-in Customer</div>
                    <div class="selected-customer-meta">Quick counter sale without saving a customer profile.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="clearCustomer">Change</button>
            </div>
        `;
        hideCustomerExtraPanel();
        closeCustomerSearchModal();
    }

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'clearCustomer') {
            selectedCustomer = null;
            document.getElementById('customer_id').value = '';
            document.getElementById('customerSearch').value = '';
            document.getElementById('selectedCustomer').innerHTML = '';
            syncShipmentZoneSelection('', 0);
            hideCustomerExtraPanel();

            if (typeof window.onCustomerCleared === 'function') {
                window.onCustomerCleared();
            }
        }
    });

    function showCreateForm(seedValue = '') {
        const normalizedSeed = seedValue.trim();
        const looksLikePhone = /^[+0-9()\-\s]+$/.test(normalizedSeed) && normalizedSeed !== '';

        customerNameInput.value = looksLikePhone ? '' : normalizedSeed;
        customerPhoneInput.value = looksLikePhone ? normalizedSeed : '';
        customerEmailInput.value = '';
        customerBillingAddressInput.value = '';
        customerShippingAddressInput.value = '';
        customerShipmentZoneSelect.value = '';
        if (customerIsMemberInput) {
            customerIsMemberInput.checked = false;
        }
        createCustomerForm.classList.add('show');
        suggestionsDropdown.classList.remove('show');

        if (looksLikePhone) {
            customerPhoneInput.focus();
            customerPhoneInput.select();
        } else if (normalizedSeed) {
            customerNameInput.focus();
        } else {
            customerPhoneInput.focus();
        }
    }

    function hideCreateForm() {
        createCustomerForm.classList.remove('show');
        if (customerSearchModal?.classList.contains('show')) {
            searchInput.focus();
        }
    }

    function saveCustomer() {
        const name = customerNameInput.value.trim();
        const phone = customerPhoneInput.value.trim();
        const email = customerEmailInput.value.trim();
        const billingAddress = customerBillingAddressInput.value.trim();
        const shippingAddress = customerShippingAddressInput.value.trim();
        const shipmentZoneId = customerShipmentZoneSelect.value;
        const isMember = customerIsMemberInput ? customerIsMemberInput.checked : false;

        if (!phone) {
            customerPhoneInput.focus();
            customerPhoneInput.classList.add('is-invalid');
            return;
        }

        customerNameInput.classList.remove('is-invalid');
        customerPhoneInput.classList.remove('is-invalid');

        $.ajax({
            url: "{{ route('posCustomer.store') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                name,
                phone,
                email,
                billing_address: billingAddress,
                shipping_address: shippingAddress,
                shipment_zone_id: shipmentZoneId,
                is_member: isMember ? 1 : 0
            },
            success: function(response) {
                const newCustomer = {
                    id: response.id,
                    name: response.name,
                    phone: response.phone,
                    is_member: !!response.is_member,
                    billing_address: response.billing_address,
                    shipping_address: response.shipping_address,
                    shipment_zone_id: response.shipment_zone_id,
                    shipment_zone_name: response.shipment_zone_name,
                    shipment_zone_charge: response.shipment_zone_charge
                };

                selectCustomer(newCustomer);
                hideCreateForm();
                toastr.success(response.message || 'Customer saved successfully!');
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message
                    || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                    || 'Error creating customer. Please try again.';
                toastr.error(errorMessage);
            }
        });
    }

    cancelBtn.addEventListener('click', hideCreateForm);
    saveBtn.addEventListener('click', saveCustomer);
    walkInCustomerBtn?.addEventListener('click', selectWalkInCustomer);

    customerSearchModal?.addEventListener('shown.bs.modal', () => {
        searchInput.focus();
        searchInput.select();
    });

    customerPhoneInput.addEventListener('input', () => {
        customerPhoneInput.classList.remove('is-invalid');
    });

    createCustomerForm.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveCustomer();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            suggestionsDropdown.classList.remove('show');
            if (createCustomerForm.classList.contains('show')) {
                hideCreateForm();
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            suggestionsDropdown.classList.remove('show');
        }
    });

    // ── Prescription Modal ──────────────────────────────────────────────────
    const prescriptionModal = document.getElementById('prescriptionModal');

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'managePrescriptionsBtn') {
            if (!selectedCustomer || !selectedCustomer.id) return;
            openPrescriptionModal(selectedCustomer.id);
        }
    });

    function openPrescriptionModal(customerId) {
        if (!prescriptionModal) return;
        const subtitle = document.getElementById('rxModalSubtitle');
        if (subtitle && selectedCustomer) {
            subtitle.textContent = selectedCustomer.name + (selectedCustomer.phone ? ' · ' + selectedCustomer.phone : '');
        }
        hideRxAddForm();
        loadPrescriptions(customerId);
        const modal = bootstrap.Modal.getOrCreateInstance(prescriptionModal);
        modal.show();
    }

    function hideRxAddForm() {
        const form = document.getElementById('rxAddForm');
        const btn  = document.getElementById('toggleAddRxBtn');
        if (form) form.style.display = 'none';
        if (btn)  btn.innerHTML = '<i class="fas fa-plus-circle" style="font-size:0.9rem;"></i> Add New Prescription';
        const fields = ['rxTitle','rxNotes','rxImage','rxImageName','rxPreviewImg','rxImagePreview'];
        if (document.getElementById('rxTitle'))   document.getElementById('rxTitle').value = '';
        if (document.getElementById('rxNotes'))   document.getElementById('rxNotes').value = '';
        if (document.getElementById('rxImage'))   document.getElementById('rxImage').value = '';
        if (document.getElementById('rxImageName')) document.getElementById('rxImageName').textContent = 'Click to choose image...';
        if (document.getElementById('rxImagePreview')) document.getElementById('rxImagePreview').style.display = 'none';
    }

    document.getElementById('toggleAddRxBtn')?.addEventListener('click', function() {
        const form = document.getElementById('rxAddForm');
        if (!form) return;
        const isVisible = form.style.display !== 'none';
        if (isVisible) {
            hideRxAddForm();
        } else {
            form.style.display = 'block';
            this.innerHTML = '<i class="fas fa-times" style="font-size:0.9rem;"></i> Cancel';
            document.getElementById('rxTitle')?.focus();
        }
    });

    document.getElementById('cancelAddRxBtn')?.addEventListener('click', hideRxAddForm);

    document.getElementById('rxImage')?.addEventListener('change', function() {
        const file = this.files[0];
        const nameEl    = document.getElementById('rxImageName');
        const previewEl = document.getElementById('rxImagePreview');
        const previewImg= document.getElementById('rxPreviewImg');
        if (file) {
            if (nameEl) nameEl.textContent = file.name;
            const reader = new FileReader();
            reader.onload = e => {
                if (previewImg) previewImg.src = e.target.result;
                if (previewEl) previewEl.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            if (nameEl) nameEl.textContent = 'Click to choose image...';
            if (previewEl) previewEl.style.display = 'none';
        }
    });

    function loadPrescriptions(customerId) {
        const list = document.getElementById('rxList');
        if (!list) return;
        list.innerHTML = `<div style="text-align:center; color:#94aec5; padding:2rem 0; font-size:0.85rem;">
            <i class="fas fa-spinner fa-spin" style="font-size:1.2rem; display:block; margin-bottom:6px;"></i>Loading...
        </div>`;

        $.getJSON('{{ url("customer") }}/' + customerId + '/prescriptions', function(data) {
            const rxCount = document.getElementById('rxCount');
            if (rxCount) rxCount.textContent = data.length;

            if (!data.length) {
                list.innerHTML = `<div style="text-align:center; padding:2.5rem 1rem;">
                    <div style="width:56px; height:56px; background:#e8f3fd; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                        <i class="fas fa-file-medical" style="color:#7ab8e8; font-size:1.5rem;"></i>
                    </div>
                    <div style="font-weight:600; color:#5a7a96; font-size:0.88rem; margin-bottom:4px;">No prescriptions yet</div>
                    <div style="color:#94aec5; font-size:0.78rem;">Click "Add New Prescription" to save one.</div>
                </div>`;
                return;
            }

            list.innerHTML = data.map(rx => `
                <div id="rx-${rx.id}" style="background:#fff; border:1px solid #d8eaf8; border-radius:0.85rem; padding:0.9rem 1rem; margin-bottom:0.65rem; box-shadow:0 2px 8px rgba(45,126,196,0.06);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:${rx.notes || rx.image_url ? '0.5rem' : '0'};">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:32px; height:32px; background:#e0eefa; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fas fa-file-alt" style="color:#2d7ec4; font-size:0.8rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700; font-size:0.85rem; color:#1a3a5c;">${rx.title || 'Prescription #' + rx.id}</div>
                                <div style="font-size:0.7rem; color:#94aec5;">${rx.created_at}</div>
                            </div>
                        </div>
                        <button type="button" class="delete-rx-btn" data-id="${rx.id}" data-customer="${customerId}"
                            style="background:#fff0f0; border:1px solid #fcc; border-radius:6px; padding:3px 8px; font-size:0.7rem; color:#e05555; cursor:pointer; font-weight:600; white-space:nowrap;">
                            <i class="fas fa-trash-alt me-1" style="font-size:0.65rem;"></i>Delete
                        </button>
                    </div>
                    ${rx.notes ? `<div style="font-size:0.8rem; color:#4a6a85; white-space:pre-wrap; background:#f5f9fd; border-radius:0.5rem; padding:0.5rem 0.65rem; line-height:1.6; border-left:3px solid #a8d0f0; margin-top:0.4rem;">${rx.notes}</div>` : ''}
                    ${rx.image_url ? `<div style="margin-top:0.5rem;">
                        <a href="${rx.image_url}" target="_blank" style="display:inline-block;">
                            <img src="${rx.image_url}" alt="Prescription image" style="max-height:140px; max-width:100%; border-radius:0.5rem; border:1px solid #c8e0f5; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                        </a>
                        <div style="font-size:0.68rem; color:#94aec5; margin-top:3px;"><i class="fas fa-expand-alt me-1"></i>Click image to view full size</div>
                    </div>` : ''}
                </div>
            `).join('');
        }).fail(function() {
            list.innerHTML = `<div style="text-align:center; color:#e05555; padding:2rem; font-size:0.85rem;">
                <i class="fas fa-exclamation-circle" style="font-size:1.3rem; display:block; margin-bottom:6px;"></i>Failed to load prescriptions.
            </div>`;
        });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-rx-btn');
        if (!btn) return;
        const rxId  = btn.dataset.id;
        const custId = btn.dataset.customer;
        if (!confirm('Delete this prescription?')) return;

        $.ajax({
            url: '{{ url("customer") }}/' + custId + '/prescriptions/' + rxId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                document.getElementById('rx-' + rxId)?.remove();
                const remaining = document.querySelectorAll('#rxList [id^="rx-"]').length;
                const rxCount = document.getElementById('rxCount');
                if (rxCount) rxCount.textContent = remaining;
                if (!remaining) loadPrescriptions(custId);
                toastr.success('Prescription deleted.');
            },
            error: function() { toastr.error('Failed to delete.'); }
        });
    });

    document.getElementById('saveRxBtn')?.addEventListener('click', function() {
        if (!selectedCustomer || !selectedCustomer.id) {
            toastr.warning('No customer selected.');
            return;
        }
        const title     = document.getElementById('rxTitle').value.trim();
        const notes     = document.getElementById('rxNotes').value.trim();
        const imageFile = document.getElementById('rxImage').files[0];

        if (!notes && !imageFile) {
            toastr.warning('Please add notes or upload an image.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (title)     formData.append('title', title);
        if (notes)     formData.append('notes', notes);
        if (imageFile) formData.append('image', imageFile);

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        $.ajax({
            url: '{{ url("customer") }}/' + selectedCustomer.id + '/prescriptions',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                toastr.success('Prescription saved!');
                hideRxAddForm();
                loadPrescriptions(selectedCustomer.id);
            },
            error: function() { toastr.error('Failed to save prescription.'); },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Prescription';
            }
        });
    });
</script>
