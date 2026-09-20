@extends('layouts.main')
@php use App\Support\Currency; @endphp
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Discount Manager</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Discount Manager</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row g-3">

            {{-- ── Left: Filters + Discount Settings ───────────────── --}}
            <div class="col-lg-3">

                {{-- Filter Card --}}
                <div class="card mb-3" style="border-radius:12px;border:1px solid #e2e8f0">
                    <div class="card-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;background:#f8fafc">
                        <span class="fw-bold" style="font-size:.85rem;color:#1e293b"><i class="bi bi-funnel-fill me-1 text-primary"></i>Filter Products</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Search</label>
                            <input type="text" id="dmSearch" class="form-control form-control-sm" placeholder="Product name…">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Category</label>
                            <div id="dmCatTree" style="max-height:260px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:6px 8px">
                                @php
                                    $rootCats = $categories->whereNull('parent_id')->sortBy('name');
                                    $catsByParent = $categories->groupBy('parent_id');
                                @endphp
                                @include('discount_manager._cat_node', ['cats' => $rootCats, 'catsByParent' => $catsByParent])
                            </div>
                        </div>
                        <button type="button" id="dmLoadBtn" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="bi bi-search me-1"></i>Load Products
                        </button>
                    </div>
                </div>

                {{-- Discount Settings Card --}}
                <div class="card" style="border-radius:12px;border:1px solid #e2e8f0;position:sticky;top:80px">
                    <div class="card-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;background:#f8fafc">
                        <span class="fw-bold" style="font-size:.85rem;color:#1e293b"><i class="bi bi-percent me-1 text-success"></i>Apply Discount</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Discount Type</label>
                            <div class="d-flex gap-2">
                                <label class="dm-type-btn" id="dmTypePct">
                                    <input type="radio" name="dm_type" value="percent" checked hidden>
                                    <i class="bi bi-percent"></i> Percent
                                </label>
                                <label class="dm-type-btn" id="dmTypeAmt">
                                    <input type="radio" name="dm_type" value="amount" hidden>
                                    <i class="bi bi-currency-dollar"></i> Amount
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Discount Value</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="dmDiscValue" class="form-control" min="0" step="0.01" value="0" placeholder="0">
                                <span class="input-group-text" id="dmDiscUnit">%</span>
                            </div>
                        </div>
                        <div class="mb-2 p-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;font-size:.78rem;color:#166534" id="dmSelectionInfo">
                            <i class="bi bi-info-circle me-1"></i> Select products from the list
                        </div>
                        <button type="button" id="dmApplyBtn" class="btn btn-success btn-sm w-100 fw-bold mb-2" disabled>
                            <i class="bi bi-check2-all me-1"></i>Apply to Selected
                        </button>
                        <button type="button" id="dmRemoveBtn" class="btn btn-outline-danger btn-sm w-100" disabled>
                            <i class="bi bi-x-circle me-1"></i>Remove Discount
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Right: Product Grid ───────────────────────────────── --}}
            <div class="col-lg-9">
                <div class="card" style="border-radius:12px;border:1px solid #e2e8f0">
                    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3" style="border-bottom:1px solid #e2e8f0;background:#f8fafc">
                        <span class="fw-bold" style="font-size:.85rem;color:#1e293b">
                            <i class="bi bi-grid-3x3-gap-fill me-1 text-primary"></i>
                            Products
                            <span class="badge bg-primary ms-1" id="dmTotalBadge" style="font-size:.7rem">0</span>
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-check-label small fw-semibold" style="cursor:pointer">
                                <input type="checkbox" id="dmSelectAll" class="form-check-input me-1">
                                Select All
                            </label>
                            <span class="text-muted small" id="dmSelectedCount">0 selected</span>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div id="dmProductGrid">
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-funnel" style="font-size:2rem;display:block;opacity:.3;margin-bottom:8px"></i>
                                <div style="font-size:.85rem">Use the filter on the left and click "Load Products"</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection

@section('footer.js')
<style>
.dm-type-btn {
    flex:1; display:flex; align-items:center; justify-content:center; gap:5px;
    padding:.35rem .6rem; border-radius:8px; cursor:pointer; font-size:.8rem; font-weight:600;
    border:1.5px solid #e2e8f0; color:#64748b; transition:all .15s; user-select:none;
}
.dm-type-btn.active { border-color:#6366f1; background:#eef2ff; color:#6366f1; }

.dm-cat-item { display:flex; align-items:center; gap:6px; padding:3px 0; font-size:.82rem; cursor:pointer; }
.dm-cat-item input[type=checkbox] { width:14px; height:14px; cursor:pointer; }
.dm-cat-children { margin-left:18px; }

.dm-prod-row {
    display:grid;
    grid-template-columns: 28px 1fr 90px 90px 90px 90px 90px;
    align-items:center; gap:.5rem;
    padding:.5rem .6rem; border-radius:9px;
    border:1px solid #f1f5f9; margin-bottom:.35rem;
    font-size:.82rem; transition:background .1s;
    cursor:pointer;
}
.dm-prod-row:hover { background:#f8fafc; }
.dm-prod-row.selected { background:#eef2ff; border-color:#a5b4fc; }
.dm-prod-name { font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dm-prod-cat  { font-size:.72rem; color:#94a3b8; }
.dm-col-hd    { font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.dm-col-val   { font-size:.82rem; font-weight:600; text-align:right; }
.dm-disc-badge { font-size:.68rem; padding:1px 6px; border-radius:5px; background:#dcfce7; color:#166534; font-weight:700; }
.dm-final-price { color:#6366f1; font-weight:800; }
.dm-grid-header {
    display:grid; grid-template-columns: 28px 1fr 90px 90px 90px 90px 90px;
    gap:.5rem; padding:.3rem .6rem; margin-bottom:.25rem;
}
</style>

<script>
const DM_PRODUCTS_URL = @json(route('discountManager.products'));
const DM_APPLY_URL    = @json(route('discountManager.apply'));
const DM_REMOVE_URL   = @json(route('discountManager.remove'));
const CSRF            = "{{ csrf_token() }}";
const CURRENCY        = @json(\App\Support\Currency::code());

let _dmProducts = [];
let _selectedIds = new Set();

// ── Type toggle ────────────────────────────────────────────
document.querySelectorAll('[name="dm_type"]').forEach(r => {
    r.closest('label').addEventListener('click', function () {
        document.querySelectorAll('.dm-type-btn').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('dmDiscUnit').textContent = r.value === 'percent' ? '%' : CURRENCY;
        rerenderPreviews();
    });
});
document.querySelector('[name="dm_type"][value="percent"]').closest('label').classList.add('active');

document.getElementById('dmDiscValue').addEventListener('input', rerenderPreviews);

// ── Category tree checkboxes ───────────────────────────────
document.querySelectorAll('.dm-cat-cb').forEach(cb => {
    cb.addEventListener('change', function () {
        const children = this.closest('.dm-cat-wrap')?.querySelectorAll('.dm-cat-children .dm-cat-cb');
        children?.forEach(c => c.checked = this.checked);
    });
});

// ── Load products ──────────────────────────────────────────
let _dmSearchTimer = null;
document.getElementById('dmSearch').addEventListener('input', function () {
    clearTimeout(_dmSearchTimer);
    _dmSearchTimer = setTimeout(loadProducts, 300);
});
document.getElementById('dmLoadBtn').addEventListener('click', loadProducts);

function loadProducts() {
    const catIds = [...document.querySelectorAll('.dm-cat-cb:checked')].map(c => c.value);
    const q      = document.getElementById('dmSearch').value.trim();
    const btn    = document.getElementById('dmLoadBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading…';

    $.getJSON(DM_PRODUCTS_URL, { category_ids: catIds, q })
        .done(data => {
            _dmProducts = data.products;
            _selectedIds.clear();
            renderGrid();
            updateSelectionInfo();
        })
        .fail(() => toastr.error('Failed to load products.'))
        .always(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search me-1"></i>Load Products';
        });
}

function fmt(n) {
    return CURRENCY + ' ' + Number(n).toFixed(2);
}

function calcFinal(salePrice, tp, type, value) {
    if (!type || value <= 0) return salePrice;
    // percent discount always on TP — never touches VAT
    if (type === 'percent') {
        const discAmt = tp > 0 ? tp * value / 100 : 0;
        return Math.max(0, salePrice - discAmt);
    }
    return Math.max(0, salePrice - value);
}

function getDiscSettings() {
    const type  = document.querySelector('[name="dm_type"]:checked')?.value || 'percent';
    const value = parseFloat(document.getElementById('dmDiscValue').value) || 0;
    return { type, value };
}

function renderGrid() {
    const grid = document.getElementById('dmProductGrid');
    document.getElementById('dmTotalBadge').textContent = _dmProducts.length;

    if (!_dmProducts.length) {
        grid.innerHTML = '<div class="text-center py-4 text-muted" style="font-size:.85rem">No products found.</div>';
        return;
    }

    const { type, value } = getDiscSettings();

    let html = `<div class="dm-grid-header">
        <div></div>
        <div class="dm-col-hd">Product</div>
        <div class="dm-col-hd text-end">TP</div>
        <div class="dm-col-hd text-end">Sale Price</div>
        <div class="dm-col-hd text-end">Current Disc</div>
        <div class="dm-col-hd text-end">New Disc</div>
        <div class="dm-col-hd text-end" style="color:#6366f1">Final Price</div>
    </div>`;

    _dmProducts.forEach(p => {
        const isSelected = _selectedIds.has(p.id);
        const newFinal   = calcFinal(p.sale_price, p.tp, type, value);
        const curDisc    = p.discount_type && p.discount_value > 0
            ? (p.discount_type === 'percent' ? p.discount_value + '%' : fmt(p.discount_value))
            : '<span class="text-muted">—</span>';
        const newDisc    = value > 0
            ? (type === 'percent' ? value + '%' : fmt(value))
            : '<span class="text-muted">—</span>';
        const tpDisplay  = p.tp > 0 ? fmt(p.tp) : '<span class="text-muted">—</span>';

        html += `<div class="dm-prod-row${isSelected ? ' selected' : ''}" data-id="${p.id}" onclick="toggleProduct(${p.id})">
            <input type="checkbox" class="form-check-input dm-check" ${isSelected ? 'checked' : ''}
                   onclick="event.stopPropagation();toggleProduct(${p.id})">
            <div>
                <div class="dm-prod-name">${p.name}</div>
                <div class="dm-prod-cat">${p.category_name}</div>
            </div>
            <div class="dm-col-val" style="color:#64748b">${tpDisplay}</div>
            <div class="dm-col-val">${fmt(p.sale_price)}</div>
            <div class="dm-col-val">${curDisc}</div>
            <div class="dm-col-val">${newDisc}</div>
            <div class="dm-col-val dm-final-price">${fmt(newFinal)}</div>
        </div>`;
    });

    grid.innerHTML = html;
    document.getElementById('dmSelectAll').checked =
        _selectedIds.size > 0 && _selectedIds.size === _dmProducts.length;
}

function rerenderPreviews() { if (_dmProducts.length) renderGrid(); }

function toggleProduct(id) {
    if (_selectedIds.has(id)) _selectedIds.delete(id);
    else _selectedIds.add(id);
    updateSelectionInfo();
    renderGrid();
}

document.getElementById('dmSelectAll').addEventListener('change', function () {
    if (this.checked) _dmProducts.forEach(p => _selectedIds.add(p.id));
    else _selectedIds.clear();
    updateSelectionInfo();
    renderGrid();
});

function updateSelectionInfo() {
    const count = _selectedIds.size;
    document.getElementById('dmSelectedCount').textContent = count + ' selected';
    document.getElementById('dmApplyBtn').disabled  = count === 0;
    document.getElementById('dmRemoveBtn').disabled = count === 0;
    document.getElementById('dmSelectionInfo').innerHTML = count > 0
        ? `<i class="bi bi-check-circle-fill me-1"></i><strong>${count}</strong> product(s) selected`
        : `<i class="bi bi-info-circle me-1"></i> Select products from the list`;
}

// ── Apply ──────────────────────────────────────────────────
document.getElementById('dmApplyBtn').addEventListener('click', function () {
    const { type, value } = getDiscSettings();
    if (value <= 0) { toastr.warning('Enter a discount value greater than 0.'); return; }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Applying…';

    $.post(DM_APPLY_URL, {
        _token: CSRF,
        product_ids: [..._selectedIds],
        discount_type: type,
        discount_value: value,
    })
    .done(r => {
        toastr.success(r.message);
        loadProducts();
    })
    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'))
    .always(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all me-1"></i>Apply to Selected';
    });
});

// ── Remove ─────────────────────────────────────────────────
document.getElementById('dmRemoveBtn').addEventListener('click', function () {
    if (!confirm('Remove discount from ' + _selectedIds.size + ' product(s)?')) return;
    const btn = this;
    btn.disabled = true;

    $.post(DM_REMOVE_URL, { _token: CSRF, product_ids: [..._selectedIds] })
        .done(r => { toastr.success(r.message); loadProducts(); })
        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'))
        .always(() => btn.disabled = false);
});
</script>
@endsection
