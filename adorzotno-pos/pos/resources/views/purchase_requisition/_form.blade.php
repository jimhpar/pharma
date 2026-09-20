@php
    $existingItems = collect(old('items', isset($pr) && $pr->exists
        ? $pr->items->map(fn($item) => [
            'sku_id'               => $item->sku_id,
            'requested_quantity'   => $item->requested_quantity,
            'current_stock'        => $item->current_stock,
            'estimated_unit_cost'  => $item->estimated_unit_cost,
            'item_note'            => $item->item_note,
            '_sku_text'            => $item->sku?->display_name ?? '',
        ])->toArray() : []));
    $isLocked = isset($pr) && $pr->exists && !$pr->isEditable();
@endphp

<style>
/* ── Item cards ───────────────────────────────────────────── */
.pr-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:.65rem; box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden; transition:box-shadow .15s; }
.pr-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.pr-card-hd { display:flex; align-items:center; gap:.6rem; padding:.55rem 1rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.pr-num { width:24px; height:24px; border-radius:50%; background:#6366f1; color:#fff; font-size:.65rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pr-pname { flex:1; min-width:0; }
.pr-pname b { font-size:.88rem; color:#1e293b; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pr-pname small { font-size:.67rem; color:#94a3b8; }
.pr-delbtn { width:28px; height:28px; border:none; border-radius:8px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; transition:background .15s, transform .1s; }
.pr-delbtn:hover { background:#fecaca; transform:scale(1.08); }
.pr-fields { display:grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap:0; }
@media(max-width:900px){ .pr-fields{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:500px){ .pr-fields{ grid-template-columns:1fr; } }
.pr-field { padding:.6rem .75rem; border-right:1px solid #eef2f7; }
.pr-field:last-child { border-right:none; }
.pr-field label { display:block; font-size:.68rem; font-weight:600; color:#64748b; margin-bottom:.28rem; }
.pr-field input.form-control { font-size:.88rem; padding:.38rem .55rem; border:1.5px solid #e8edf2; border-radius:8px; background:#fdfdfe; width:100%; color:#1e293b; font-weight:500; transition:border-color .15s, box-shadow .15s; }
.pr-field input.form-control:focus { border-color:#818cf8; background:#fff; box-shadow:0 0 0 3px rgba(129,140,248,.12); outline:none; }
.pr-field input[readonly] { background:#f8fafc; color:#64748b; cursor:default; }

/* ── Stock badges ─────────────────────────────────────────── */
.pr-stock-badge { font-size:.71rem; font-weight:700; padding:2px 8px; border-radius:6px; white-space:nowrap; }
.stock-ok   { background:#dcfce7; color:#166534; }
.stock-low  { background:#fef9c3; color:#854d0e; }
.stock-zero { background:#fee2e2; color:#991b1b; }

/* ── Product search box ───────────────────────────────────── */
.pr-search-wrap {
    position: relative;
    background: linear-gradient(135deg, #f0f4ff 0%, #f5f3ff 100%);
    border: 2px dashed #a5b4fc;
    border-radius: 14px;
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
    transition: border-color .2s, background .2s;
}
.pr-search-wrap:focus-within {
    border-color: #6366f1;
    background: linear-gradient(135deg, #eef2ff 0%, #ede9fe 100%);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.pr-search-label {
    font-size: .72rem; font-weight: 700; color: #6366f1;
    text-transform: uppercase; letter-spacing: .07em;
    margin-bottom: .45rem; display: flex; align-items: center; gap: 5px;
}
.pr-search-inner {
    display: flex; align-items: center; gap: .5rem;
    background: #fff; border: 1.5px solid #c7d2fe;
    border-radius: 10px; padding: .42rem .7rem;
    box-shadow: 0 1px 4px rgba(99,102,241,.08);
    transition: border-color .15s;
}
.pr-search-wrap:focus-within .pr-search-inner { border-color: #6366f1; }
.pr-search-icon { color: #818cf8; font-size: 1rem; flex-shrink: 0; }
.pr-search-input {
    border: none; outline: none; width: 100%; font-size: .9rem;
    color: #1e293b; background: transparent; font-weight: 500;
}
.pr-search-input::placeholder { color: #a5b4fc; font-weight: 400; }
.pr-search-spinner { display:none; width:16px; height:16px; border:2px solid #e0e7ff; border-top-color:#6366f1; border-radius:50%; animation:pr-spin .6s linear infinite; flex-shrink:0; }
@keyframes pr-spin { to { transform:rotate(360deg); } }
.pr-search-hint { font-size:.73rem; color:#94a3b8; margin-top:.4rem; display:flex; align-items:center; gap:4px; }

/* ── Dropdown ─────────────────────────────────────────────── */
.pr-drop {
    position: absolute; left: 0; right: 0; top: calc(100% + 4px);
    background: #fff; border: 1px solid #e0e7ff;
    border-radius: 12px; box-shadow: 0 8px 32px rgba(99,102,241,.14);
    z-index: 9999; max-height: 340px; overflow-y: auto;
    display: none;
}
.pr-drop-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .65rem 1rem; cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: background .12s;
}
.pr-drop-item:last-child { border-bottom: none; }
.pr-drop-item:hover { background: #eef2ff; }
.pr-drop-item:hover .pr-drop-add { opacity:1; transform:scale(1); }
.pr-drop-icon {
    width: 36px; height: 36px; border-radius: 9px;
    background: linear-gradient(135deg,#e0e7ff,#ede9fe);
    display: flex; align-items:center; justify-content:center;
    flex-shrink:0; color:#6366f1; font-size:.9rem;
}
.pr-drop-info { flex:1; min-width:0; }
.pr-drop-name { font-size:.85rem; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pr-drop-generic { font-size:.72rem; color:#6366f1; font-weight:600; }
.pr-drop-sub { font-size:.7rem; color:#94a3b8; }
.pr-drop-right { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.pr-drop-add {
    opacity:0; transform:scale(.85); transition:opacity .15s, transform .15s;
    width:26px; height:26px; border-radius:7px;
    background:#6366f1; color:#fff; border:none;
    display:flex; align-items:center; justify-content:center;
    font-size:.8rem; cursor:pointer;
}
.pr-drop-empty { padding:1.2rem; text-align:center; color:#94a3b8; font-size:.83rem; }
.pr-drop-empty i { font-size:1.4rem; display:block; margin-bottom:5px; opacity:.4; }
</style>

{{-- ── Header Info ───────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">PR Number <span class="text-danger">*</span></label>
        <input type="text" name="requisition_no" class="form-control form-control-sm @error('requisition_no') is-invalid @enderror"
               value="{{ old('requisition_no', $pr->requisition_no) }}" {{ $isLocked ? 'readonly' : '' }} required>
        @error('requisition_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Date <span class="text-danger">*</span></label>
        <input type="date" name="requisition_date" class="form-control form-control-sm @error('requisition_date') is-invalid @enderror"
               value="{{ old('requisition_date', $pr->requisition_date?->format('Y-m-d')) }}" {{ $isLocked ? 'readonly' : '' }} required>
        @error('requisition_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Branch <span class="text-danger">*</span></label>
        <select name="branch_id" id="prBranchId" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" {{ $isLocked ? 'disabled' : '' }} required>
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ old('branch_id', $pr->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1">Warehouse <span class="text-danger">*</span></label>
        <select name="warehouse_id" id="prWarehouseId" class="form-select form-select-sm @error('warehouse_id') is-invalid @enderror" {{ $isLocked ? 'disabled' : '' }} required>
            <option value="">Select Warehouse</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ old('warehouse_id', $pr->warehouse_id) == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold mb-1">Reason / Purpose</label>
        <input type="text" name="reason" class="form-control form-control-sm"
               value="{{ old('reason', $pr->reason) }}" {{ $isLocked ? 'readonly' : '' }}
               placeholder="e.g. Monthly restock, Low stock alert…">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Status</label>
        <select name="status" class="form-select form-select-sm" {{ $isLocked ? 'disabled' : '' }}>
            <option value="draft"     {{ old('status', $pr->status) === 'draft'     ? 'selected' : '' }}>Draft</option>
            <option value="submitted" {{ old('status', $pr->status) === 'submitted' ? 'selected' : '' }}>Submit for Approval</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Internal Note</label>
        <input type="text" name="note" class="form-control form-control-sm"
               value="{{ old('note', $pr->note) }}" {{ $isLocked ? 'readonly' : '' }}>
    </div>
</div>

{{-- ── Locked Banner ─────────────────────────────────────────── --}}
@if($isLocked)
<div class="alert alert-warning py-2 mb-3" style="font-size:.82rem">
    <i class="bi bi-lock-fill me-1"></i>
    This requisition is <strong>{{ ucfirst($pr->status) }}</strong> and cannot be edited.
    @if($pr->rejection_note)
        <div class="mt-1"><strong>Rejection reason:</strong> {{ $pr->rejection_note }}</div>
    @endif
</div>
@endif

{{-- ── Items Section Header ─────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <span class="fw-bold" style="font-size:.9rem;color:#1e293b">
        <i class="bi bi-list-ul me-1 text-primary"></i>Requisition Items
        <span id="prItemCount" class="badge bg-primary ms-1" style="font-size:.7rem">{{ count($existingItems) }}</span>
    </span>
</div>

@error('items')<div class="alert alert-danger py-1 mb-2" style="font-size:.82rem">{{ $message }}</div>@enderror

{{-- ── Product Search Box ────────────────────────────────────── --}}
@unless($isLocked)
<div class="pr-search-wrap" id="prSearchWrap">
    <div class="pr-search-label">
        <i class="bi bi-plus-circle-fill"></i> Add Product
    </div>
    <div class="pr-search-inner">
        <i class="bi bi-search pr-search-icon"></i>
        <input type="text" id="prSkuSearchInput" class="pr-search-input"
               placeholder="Type medicine name or SKU code…" autocomplete="off">
        <div class="pr-search-spinner" id="prSearchSpinner"></div>
    </div>
    <div class="pr-search-hint">
        <i class="bi bi-info-circle"></i> Type at least 1 character — click any result to add instantly
    </div>
    <div class="pr-drop" id="prSkuDrop"></div>
</div>
@endunless

{{-- ── Items List ────────────────────────────────────────────── --}}
<div id="prItemsContainer">
    @forelse($existingItems as $idx => $item)
        @include('purchase_requisition._item_row', ['idx' => $idx, 'item' => $item, 'isLocked' => $isLocked])
    @empty
        @unless($isLocked)
        <div id="prEmptyMsg" class="text-center py-5" style="color:#c7d2fe">
            <i class="bi bi-box-seam" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.5"></i>
            <div style="font-size:.85rem;color:#94a3b8;font-weight:500">No items added yet</div>
            <div style="font-size:.75rem;color:#c4cdd8;margin-top:2px">Search and click a product above to add it</div>
        </div>
        @endunless
    @endforelse
</div>

@unless($isLocked)
<script>
const PR_SKU_SEARCH_URL = @json(route('purchaseRequisition.skuSearch'));
let prItemIndex = {{ count($existingItems) }};
let _prSearchTimer = null;

const searchInput = document.getElementById('prSkuSearchInput');
const drop        = document.getElementById('prSkuDrop');
const spinner     = document.getElementById('prSearchSpinner');

function stockClass(n) {
    return n > 10 ? 'stock-ok' : (n > 0 ? 'stock-low' : 'stock-zero');
}
function stockLabel(n) {
    return n > 10 ? `<i class="bi bi-check-circle-fill me-1"></i>Stock: ${n}`
         : n > 0  ? `<i class="bi bi-exclamation-circle-fill me-1"></i>Stock: ${n}`
         :           `<i class="bi bi-x-circle-fill me-1"></i>Out of Stock`;
}

searchInput.addEventListener('input', function () {
    clearTimeout(_prSearchTimer);
    const q = this.value.trim();
    if (!q) { drop.style.display = 'none'; return; }
    spinner.style.display = 'block';
    _prSearchTimer = setTimeout(() => fetchSkus(q), 220);
});

function fetchSkus(q) {
    const warehouseId = document.getElementById('prWarehouseId')?.value || '';
    $.getJSON(PR_SKU_SEARCH_URL, { q, warehouse_id: warehouseId }, function (data) {
        spinner.style.display = 'none';
        drop.innerHTML = '';

        if (!data.results.length) {
            drop.innerHTML = `<div class="pr-drop-empty"><i class="bi bi-search"></i>No products found for "<strong>${q}</strong>"</div>`;
        } else {
            data.results.forEach(r => {
                const sc = stockClass(r.current_stock);
                const nameParts = r.text.split(' — ');
                const productName = nameParts[0] || r.text;
                const skuCode     = nameParts[1] || '';

                const el = document.createElement('div');
                el.className = 'pr-drop-item';
                el.innerHTML = `
                    <div class="pr-drop-icon"><i class="bi bi-capsule"></i></div>
                    <div class="pr-drop-info">
                        <div class="pr-drop-name">${productName}</div>
                        ${skuCode ? `<div class="pr-drop-sub"><i class="bi bi-upc me-1"></i>${skuCode}</div>` : ''}
                    </div>
                    <div class="pr-drop-right">
                        <span class="pr-stock-badge ${sc}">${stockLabel(r.current_stock)}</span>
                        <button type="button" class="pr-drop-add" title="Add"><i class="bi bi-plus-lg"></i></button>
                    </div>`;

                el.addEventListener('click', () => {
                    addPrItem(r);
                    searchInput.value = '';
                    drop.style.display = 'none';
                    searchInput.focus();
                });
                drop.appendChild(el);
            });
        }
        drop.style.display = 'block';
    }).fail(() => { spinner.style.display = 'none'; });
}

document.addEventListener('click', e => {
    if (!e.target.closest('#prSearchWrap')) drop.style.display = 'none';
});

function addPrItem(sku) {
    document.getElementById('prEmptyMsg')?.remove();

    const idx = prItemIndex++;
    const sc  = stockClass(sku.current_stock);
    const html = `
    <div class="pr-card" id="pr-item-${idx}" style="animation:pr-fadein .2s ease">
        <div class="pr-card-hd">
            <div class="pr-num">${document.querySelectorAll('#prItemsContainer .pr-card').length + 1}</div>
            <div class="pr-pname">
                <b>${sku.text.split(' — ')[0]}</b>
                <small><i class="bi bi-upc me-1"></i>${sku.text.split(' — ')[1] || 'SKU #' + sku.id}</small>
            </div>
            <span class="pr-stock-badge ${sc} me-2">${stockLabel(sku.current_stock)}</span>
            <button type="button" class="pr-delbtn" onclick="removePrItem(${idx})" title="Remove">
                <i class="bi bi-trash3" style="font-size:.72rem"></i>
            </button>
        </div>
        <div class="pr-fields">
            <input type="hidden" name="items[${idx}][sku_id]" value="${sku.id}">
            <div class="pr-field">
                <label><i class="bi bi-hash me-1"></i>Requested Qty <span style="color:#ef4444">*</span></label>
                <input type="number" name="items[${idx}][requested_quantity]" class="form-control" min="1" value="1" required autofocus>
            </div>
            <div class="pr-field">
                <label><i class="bi bi-boxes me-1"></i>Current Stock</label>
                <input type="number" name="items[${idx}][current_stock]" class="form-control" value="${sku.current_stock}" readonly>
            </div>
            <div class="pr-field">
                <label><i class="bi bi-currency-dollar me-1"></i>Est. Unit Cost</label>
                <input type="number" name="items[${idx}][estimated_unit_cost]" class="form-control" min="0" step="0.01" value="${sku.retail_price || ''}" placeholder="0.00">
            </div>
            <div class="pr-field">
                <label><i class="bi bi-chat-left-text me-1"></i>Note</label>
                <input type="text" name="items[${idx}][item_note]" class="form-control" placeholder="Optional note…">
            </div>
        </div>
    </div>`;

    document.getElementById('prItemsContainer').insertAdjacentHTML('beforeend', html);
    updateItemCount();

    // Focus the qty input of the new card
    const newCard = document.getElementById('pr-item-' + idx);
    newCard?.querySelector('input[type=number]')?.focus();
    newCard?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removePrItem(idx) {
    document.getElementById('pr-item-' + idx)?.remove();
    updateItemCount();
    if (!document.querySelector('#prItemsContainer .pr-card')) {
        document.getElementById('prItemsContainer').innerHTML = `
        <div id="prEmptyMsg" class="text-center py-5" style="color:#c7d2fe">
            <i class="bi bi-box-seam" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.5"></i>
            <div style="font-size:.85rem;color:#94a3b8;font-weight:500">No items added yet</div>
            <div style="font-size:.75rem;color:#c4cdd8;margin-top:2px">Search and click a product above to add it</div>
        </div>`;
    }
}

function updateItemCount() {
    const count = document.querySelectorAll('#prItemsContainer .pr-card').length;
    const badge = document.getElementById('prItemCount');
    if (badge) badge.textContent = count;
}
</script>
<style>
@keyframes pr-fadein { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
</style>
@endunless
