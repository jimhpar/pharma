@php
    $existingItems = collect(old('items', isset($purchaseOrder) && $purchaseOrder->exists
        ? $purchaseOrder->items->map(fn($item) => [
            'sku_id'          => $item->sku_id,
            'quantity'        => $item->quantity,
            'bonus_qty'       => $item->bonus_qty ?? 0,
            'unit_cost'       => $item->unit_cost,
            'unit_price'      => $item->unit_price,
            'mrp'             => $item->mrp,
            'sale_price'      => $item->sale_price,
            'discount_amount' => $item->discount_amount,
            'tax_amount'      => $item->tax_amount,
            'batch_no'        => $item->batch_no,
            'expiry_date'     => optional($item->expiry_date)->format('Y-m-d'),
            'carton_plan'     => $item->carton_plan ?? [],
        ])->toArray() : []));
    $isLocked = isset($purchaseOrder) && $purchaseOrder->exists && $purchaseOrder->items->sum('received_quantity') > 0;
@endphp

<style>
.po-card {
    background:#fff; border:1px solid #e2e8f0;
    border-radius:12px; margin-bottom:.65rem;
    box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden;
}
.po-card-hd {
    display:flex; align-items:center; gap:.6rem;
    padding:.55rem 1rem;
    background:#f8fafc; border-bottom:1px solid #e2e8f0;
}
.po-num {
    width:22px; height:22px; border-radius:50%;
    background:#6366f1; color:#fff;
    font-size:.63rem; font-weight:800;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.po-pname { flex:1; min-width:0; }
.po-pname b { font-size:.88rem; color:#1e293b; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.po-pname small { font-size:.67rem; color:#94a3b8; }
.po-linetotal {
    font-size:.85rem; font-weight:800; color:#6366f1;
    background:#eff0ff; border-radius:7px; padding:2px 10px; white-space:nowrap;
}
.po-delbtn {
    width:26px; height:26px; border:none; border-radius:7px;
    background:#fee2e2; color:#dc2626;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; transition:background .15s;
}
.po-delbtn:hover { background:#fecaca; }

/* fields grid — 9 columns */
.po-fields {
    display:grid;
    grid-template-columns: repeat(9, 1fr);
    gap:0; padding:0;
    border-bottom:1px solid #eef2f7;
}
@media(max-width:1200px){ .po-fields{ grid-template-columns:repeat(5,1fr); } }
@media(max-width:700px){  .po-fields{ grid-template-columns:repeat(3,1fr); } }

.po-field {
    padding:.6rem .75rem;
    border-right:1px solid #eef2f7;
    position:relative;
}
.po-field:last-child { border-right:none; }
@media(max-width:1200px){
    .po-field:nth-child(5n){ border-right:none; }
    .po-field:nth-child(n+6){ border-top:1px solid #eef2f7; }
}
@media(max-width:700px){
    .po-field:nth-child(3n){ border-right:none; }
    .po-field:nth-child(n+4){ border-top:1px solid #eef2f7; }
}

.po-field label {
    display:block;
    font-size:.68rem; font-weight:600;
    color:#64748b; margin-bottom:.28rem;
    white-space:nowrap;
}

.po-field input.form-control {
    font-size:.88rem; padding:.38rem .55rem;
    border:1.5px solid #e8edf2;
    border-radius:8px; background:#fdfdfe;
    width:100%; color:#1e293b; font-weight:500;
    transition:border-color .15s, box-shadow .15s, background .15s;
}
.po-field input.form-control:focus {
    border-color:#818cf8; background:#fff;
    box-shadow:0 0 0 3px rgba(129,140,248,.12); outline:none;
}
.po-field input.form-control::placeholder { color:#c8d0da; font-weight:400; }

/* required fields — slightly highlighted */
.po-field.req-field input.form-control {
    border-color:#c7d2fe; background:#fafafe;
}
.po-field.req-field input.form-control:focus {
    border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12);
}
/* price fields — green tint */
.po-field.price-field input.form-control {
    border-color:#bbf7d0; background:#f0fdf4;
}
.po-field.price-field input.form-control:focus {
    border-color:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.1);
}
/* tax field — red tint */
.po-field.tax-field input.form-control {
    border-color:#fecaca; background:#fff5f5;
}
.po-field.tax-field input.form-control:focus {
    border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.1);
}

/* carton strip */
.po-carton {
    display:flex; align-items:center; gap:.7rem;
    padding:.5rem 1rem;
    background:linear-gradient(90deg,#f8f9ff,#fafbff);
    border-top:1px solid #eef2f7;
}
.po-carton-lbl {
    font-size:.6rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.08em; color:#a5b4fc; white-space:nowrap;
    display:flex; align-items:center; gap:.3rem;
}
.po-carton .form-control {
    font-size:.85rem; padding:.3rem .6rem;
    border:1.5px solid #dde2fb; border-radius:8px; background:#fff;
    color:#374151; font-weight:500;
}
.po-carton .form-control:focus {
    border-color:#818cf8; background:#fff;
    box-shadow:0 0 0 3px rgba(129,140,248,.12); outline:none;
}

/* summary */
.sum-row { display:flex; justify-content:space-between; padding:.3rem 0; font-size:.875rem; }
.sum-row+.sum-row { border-top:1px solid #f1f5f9; }
.sum-row .l { color:#64748b; } .sum-row .v { font-weight:600; }
.sum-grand { background:linear-gradient(135deg,#6366f1,#818cf8); border-radius:10px; padding:.6rem 1rem; margin-top:.6rem; display:flex; justify-content:space-between; align-items:center; }
.sum-grand .l { color:rgba(255,255,255,.85); font-weight:600; }
.sum-grand .v { color:#fff; font-weight:900; font-size:1.1rem; }
.sum-bal { background:#fefce8; border:1px solid #fde047; border-radius:10px; padding:.45rem 1rem; margin-top:.35rem; display:flex; justify-content:space-between; align-items:center; }
.sum-bal .l { color:#854d0e; font-weight:600; font-size:.875rem; } .sum-bal .v { color:#854d0e; font-weight:800; }

/* search */
.po-search { background:#f5f6ff; border:1px solid #dde2fb; border-radius:12px; padding:.8rem 1rem; margin-bottom:.75rem; }
.po-search-lbl { font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6366f1; margin-bottom:.35rem; }
.select2-container--bootstrap-5 .select2-selection--single { height:44px !important; border:1.5px solid #a5b4fc !important; border-radius:9px !important; }
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height:42px !important; font-size:.9rem; padding-left:14px !important; }
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height:42px !important; }

.po-empty { text-align:center; padding:2.5rem; border:2px dashed #dde2fb; border-radius:12px; background:#f8f9ff; color:#94a3b8; font-size:.875rem; margin-bottom:.75rem; }
.po-empty i { font-size:2rem; display:block; margin-bottom:.4rem; color:#c7d2fe; }
.po-info { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.2rem; margin-bottom:1.1rem; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.po-sh { font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94a3b8; margin-bottom:.7rem; padding-bottom:.35rem; border-bottom:2px solid #f1f5f9; }
</style>

@if($isLocked)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-lock-fill"></i> This purchase order has received stock. Editing is locked.
</div>
@endif
@error('purchase_order')<div class="alert alert-danger">{{ $message }}</div>@enderror
@error('items')<div class="alert alert-danger">{{ $message }}</div>@enderror

{{-- ══ ORDER INFO ══ --}}
<div class="po-info">
    <div class="po-sh">Order Information</div>
    <div class="row g-3">

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Supplier <span class="text-danger">*</span></label>
            <select class="form-control {{ $errors->has('supplier_id') ? 'is-invalid' : '' }}" name="supplier_id" {{ $isLocked ? 'disabled' : '' }}>
                <option value="">— Select Supplier —</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (string)old('supplier_id', $purchaseOrder->supplier_id) === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Supplier Invoice No</label>
            <input class="form-control {{ $errors->has('invoice_no') ? 'is-invalid' : '' }}" name="invoice_no"
                value="{{ old('invoice_no', $purchaseOrder->invoice_no ?? '') }}"
                placeholder="e.g. INV-001" {{ $isLocked ? 'disabled' : '' }}>
            @error('invoice_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Purchase Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control {{ $errors->has('purchase_date') ? 'is-invalid' : '' }}" name="purchase_date"
                value="{{ old('purchase_date', optional($purchaseOrder->purchase_date)->format('Y-m-d') ?: $purchaseOrder->purchase_date) }}"
                {{ $isLocked ? 'disabled' : '' }}>
            @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select class="form-control" name="status" {{ $isLocked ? 'disabled' : '' }}>
                <option value="draft"    {{ old('status', $purchaseOrder->status) === 'draft'    ? 'selected' : '' }}>Draft</option>
                <option value="ordered"  {{ old('status', $purchaseOrder->status) === 'ordered'  ? 'selected' : '' }}>Ordered</option>
                <option value="canceled" {{ old('status', $purchaseOrder->status) === 'canceled' ? 'selected' : '' }}>Canceled</option>
            </select>
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Branch <span class="text-danger">*</span></label>
            <select class="form-control {{ $errors->has('branch_id') ? 'is-invalid' : '' }}" name="branch_id" {{ $isLocked ? 'disabled' : '' }}>
                <option value="">— Select Branch —</option>
                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ (string)old('branch_id', $purchaseOrder->branch_id) === (string)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Warehouse <span class="text-danger">*</span></label>
            <select class="form-control {{ $errors->has('warehouse_id') ? 'is-invalid' : '' }}" name="warehouse_id" {{ $isLocked ? 'disabled' : '' }}>
                <option value="">— Select Warehouse —</option>
                @foreach($warehouses as $w)
                <option value="{{ $w->id }}" {{ (string)old('warehouse_id', $purchaseOrder->warehouse_id) === (string)$w->id ? 'selected' : '' }}>
                    {{ $w->name }} ({{ $w->branch?->name ?? 'N/A' }})
                </option>
                @endforeach
            </select>
            @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Purchase No</label>
            <input class="form-control" name="purchase_no"
                value="{{ old('purchase_no', $purchaseOrder->purchase_no) }}"
                placeholder="Auto-generated" {{ $isLocked ? 'disabled' : '' }}>
        </div>

        <div class="col-sm-3 col-6">
            <label class="form-label small fw-semibold mb-1">Note <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" class="form-control" name="note"
                value="{{ old('note', $purchaseOrder->note) }}"
                placeholder="Remarks..." {{ $isLocked ? 'disabled' : '' }}>
        </div>

    </div>
</div>

{{-- ══ ITEMS ══ --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <div class="po-sh mb-0">Purchase Items</div>
    <span class="badge bg-primary" id="itemCountBadge" style="font-size:.7rem">0 items</span>
</div>

@if(!$isLocked)
<div class="po-search">
    <div class="po-search-lbl"><i class="bi bi-search me-1"></i>Search & Add Product</div>
    <div class="d-flex gap-2">
        <div class="flex-grow-1">
            <select id="productQuickAdd" style="width:100%">
                <option value=""></option>
            </select>
        </div>
        <button type="button" id="addBlankRow" class="btn btn-outline-secondary" style="height:44px;white-space:nowrap">
            <i class="bi bi-plus"></i> Blank
        </button>
    </div>
</div>
@endif

<div id="poItemsList">
@forelse($existingItems as $index => $item)
@php
    $sku   = $skus->firstWhere('id', $item['sku_id'] ?? null);
    $qty   = (float)($item['quantity'] ?? 1);
    $cost  = (float)($item['unit_cost'] ?? 0);
    $dis   = (float)($item['discount_amount'] ?? 0);
    $vat   = (float)($item['tax_amount'] ?? 0);
    $tot   = ($qty * $cost) - $dis + $vat;
    $cp    = $item['carton_plan'][0] ?? [];
@endphp
<div class="po-card" id="po-card-{{ $index }}">
    <div class="po-card-hd">
        <span class="po-num item-num">{{ $index + 1 }}</span>
        <div class="po-pname">
            <input type="hidden" name="items[{{ $index }}][sku_id]" value="{{ $item['sku_id'] ?? '' }}" class="sku-id-input">
            <b>{{ $sku?->display_name ?? 'Unknown Product' }}</b>
            <small>SKU #{{ $item['sku_id'] ?? '—' }}</small>
        </div>
        <div class="po-linetotal line-total-cell">৳ {{ number_format($tot, 2) }}</div>
        @if(!$isLocked)
        <button type="button" class="po-delbtn remove-item-btn"><i class="bi bi-x-lg"></i></button>
        @endif
    </div>

    <div class="po-fields">
        <div class="po-field">
            <label>Batch No</label>
            <input type="text" class="form-control" name="items[{{ $index }}][batch_no]"
                value="{{ $item['batch_no'] ?? '' }}" placeholder="BT-2024" {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field">
            <label>Expiry Date</label>
            <input type="date" class="form-control" name="items[{{ $index }}][expiry_date]"
                value="{{ $item['expiry_date'] ?? '' }}" {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field req-field">
            <label>Qty *</label>
            <input type="number" min="1" class="form-control qty-input item-calc"
                name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}"
                {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field">
            <label>Bonus Qty</label>
            <input type="number" min="0" class="form-control item-calc"
                name="items[{{ $index }}][bonus_qty]" value="{{ $item['bonus_qty'] ?? 0 }}"
                {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field req-field">
            <label>Trade Price (TP) *</label>
            <input type="number" step="0.01" min="0" class="form-control cost-input item-calc"
                name="items[{{ $index }}][unit_cost]" value="{{ $item['unit_cost'] ?? 0 }}"
                {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field">
            <label>Unit Price</label>
            <input type="number" step="0.01" min="0" class="form-control"
                name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}"
                placeholder="0.00" {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field tax-field">
            <label>VAT (৳)</label>
            <input type="number" step="0.01" min="0" class="form-control tax-input item-calc"
                name="items[{{ $index }}][tax_amount]" value="{{ $item['tax_amount'] ?? 0 }}"
                {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field">
            <label>MRP</label>
            <input type="number" step="0.01" min="0" class="form-control"
                name="items[{{ $index }}][mrp]" value="{{ $item['mrp'] ?? '' }}"
                placeholder="0.00" {{ $isLocked ? 'disabled' : '' }}>
        </div>
        <div class="po-field">
            <label>Sale Price</label>
            <input type="number" step="0.01" min="0" class="form-control"
                name="items[{{ $index }}][sale_price]" value="{{ $item['sale_price'] ?? '' }}"
                placeholder="0.00" {{ $isLocked ? 'disabled' : '' }}>
        </div>
    </div>

    {{-- Carton strip --}}
    <div class="po-carton">
        <span class="po-carton-lbl"><i class="bi bi-box2-fill"></i> Carton</span>
        <input type="text" class="form-control" style="max-width:180px" placeholder="Code (optional)"
            name="items[{{ $index }}][carton_plan][0][name]"
            value="{{ $cp['name'] ?? '' }}" {{ $isLocked ? 'disabled' : '' }}>
        <span class="po-carton-lbl ms-2">Boxes</span>
        <input type="number" min="1" class="form-control" style="max-width:90px" placeholder="0"
            name="items[{{ $index }}][carton_plan][0][boxes]"
            value="{{ $cp['boxes'] ?? '' }}" {{ $isLocked ? 'disabled' : '' }}>
        <input type="hidden" name="items[{{ $index }}][carton_plan][0][units_per_box]" value="{{ $cp['units_per_box'] ?? 1 }}">
    </div>
</div>
@empty
<div class="po-empty" id="emptyState">
    <i class="bi bi-inbox"></i>
    Use the search bar above to add products
</div>
@endforelse
</div>

{{-- ══ PAYMENT + SUMMARY ══ --}}
<div class="row g-3 align-items-start mb-3 mt-1">
    <div class="col-md-5 col-12">
        <div class="po-info mb-0">
            <div class="po-sh">Payment</div>
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1">Other Charges (৳)</label>
                <div class="input-group">
                    <span class="input-group-text">৳</span>
                    <input type="number" step="0.01" min="0" id="other_charge_total"
                        class="form-control {{ $errors->has('other_charge_total') ? 'is-invalid' : '' }}"
                        name="other_charge_total"
                        value="{{ old('other_charge_total', $purchaseOrder->other_charge_total ?? 0) }}"
                        {{ $isLocked ? 'disabled' : '' }}>
                </div>
                @error('other_charge_total')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Amount Paid (৳)</label>
                <div class="input-group">
                    <span class="input-group-text">৳</span>
                    <input type="number" step="0.01" min="0" id="paid_total"
                        class="form-control {{ $errors->has('paid_total') ? 'is-invalid' : '' }}"
                        name="paid_total"
                        value="{{ old('paid_total', $purchaseOrder->paid_total ?? 0) }}"
                        {{ $isLocked ? 'disabled' : '' }}>
                </div>
                @error('paid_total')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="col-md-7 col-12">
        <div class="po-info mb-0">
            <div class="po-sh">Summary</div>
            <div class="sum-row"><span class="l">Sub Total</span>     <span class="v" id="s-sub">৳ 0.00</span></div>
            <div class="sum-row"><span class="l">VAT</span>           <span class="v text-success" id="s-vat">+ ৳ 0.00</span></div>
            <div class="sum-row"><span class="l">Other Charges</span> <span class="v" id="s-oth">+ ৳ 0.00</span></div>
            <div class="sum-grand"><span class="l">Grand Total</span><span class="v" id="s-grand">৳ 0.00</span></div>
            <div class="sum-bal"><span class="l">Balance Due</span><span class="v" id="s-bal">৳ 0.00</span></div>
        </div>
    </div>
</div>

@if(!$isLocked)
<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="{{ route('purchaseOrder.show') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">
        <i class="bi bi-check-lg me-1"></i> Save Purchase Order
    </button>
</div>
@endif

@section('footer.js')
@parent
<script>
(function(){
    const skuMap = {};
    @foreach($skus as $sku) skuMap[{{ $sku->id }}] = @json($sku->display_name); @endforeach
    const skuSearchUrl = @json(route('purchaseOrder.skuSearch'));

    let idx = {{ $existingItems->count() }};
    const f   = n => parseFloat(n||0).toFixed(2);
    const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    /* ── Recalc ── */
    function recalc(){
        let sub=0, vat=0;
        document.querySelectorAll('#poItemsList .po-card').forEach(card => {
            const q = +(card.querySelector('.qty-input')?.value  || 0);
            const p = +(card.querySelector('.cost-input')?.value || 0);
            const v = +(card.querySelector('.tax-input')?.value  || 0);
            const t = (q * p) + v;
            sub += q*p; vat += v;
            const el = card.querySelector('.line-total-cell');
            if(el) el.textContent = '৳ ' + t.toFixed(2);
        });
        const oth  = +(document.getElementById('other_charge_total')?.value || 0);
        const paid = +(document.getElementById('paid_total')?.value          || 0);
        const grand = sub + vat + oth;
        document.getElementById('s-sub').textContent   = '৳ ' + f(sub);
        document.getElementById('s-vat').textContent   = '+ ৳ ' + f(vat);
        document.getElementById('s-oth').textContent   = '+ ৳ ' + f(oth);
        document.getElementById('s-grand').textContent = '৳ ' + f(grand);
        document.getElementById('s-bal').textContent   = '৳ ' + f(Math.max(0, grand - paid));
    }

    /* ── Build card HTML ── */
    function buildCard(skuId, skuLabel, retailPrice, mrpVal){
        const i = idx;
        const rp  = retailPrice ? parseFloat(retailPrice).toFixed(2) : '';
        const mrp = mrpVal      ? parseFloat(mrpVal).toFixed(2)      : rp;
        return `
        <div class="po-card" id="po-card-${i}">
            <div class="po-card-hd">
                <span class="po-num item-num"></span>
                <div class="po-pname">
                    <input type="hidden" name="items[${i}][sku_id]" value="${skuId}" class="sku-id-input">
                    <b>${esc(skuLabel)}</b>
                    <small>SKU #${skuId||'—'}</small>
                </div>
                <div class="po-linetotal line-total-cell">৳ 0.00</div>
                <button type="button" class="po-delbtn remove-item-btn"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="po-fields">
                <div class="po-field">
                    <label>Batch No</label>
                    <input type="text" class="form-control" name="items[${i}][batch_no]" placeholder="BT-2024">
                </div>
                <div class="po-field">
                    <label>Expiry Date</label>
                    <input type="date" class="form-control" name="items[${i}][expiry_date]">
                </div>
                <div class="po-field req-field">
                    <label>Qty *</label>
                    <input type="number" min="1" class="form-control qty-input item-calc" name="items[${i}][quantity]" value="1">
                </div>
                <div class="po-field">
                    <label>Bonus Qty</label>
                    <input type="number" min="0" class="form-control item-calc" name="items[${i}][bonus_qty]" value="0">
                </div>
                <div class="po-field req-field">
                    <label>Trade Price (TP) *</label>
                    <input type="number" step="0.01" min="0" class="form-control cost-input item-calc" name="items[${i}][unit_cost]" value="0">
                </div>
                <div class="po-field">
                    <label>Unit Price</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="items[${i}][unit_price]" placeholder="0.00">
                </div>
                <div class="po-field tax-field">
                    <label>VAT (৳)</label>
                    <input type="number" step="0.01" min="0" class="form-control tax-input item-calc" name="items[${i}][tax_amount]" value="0">
                </div>
                <div class="po-field">
                    <label>MRP</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="items[${i}][mrp]" value="${mrp}" placeholder="0.00">
                </div>
                <div class="po-field">
                    <label>Sale Price</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="items[${i}][sale_price]" value="${rp}" placeholder="0.00">
                </div>
            </div>
            <div class="po-carton">
                <span class="po-carton-lbl"><i class="bi bi-box2-fill"></i> Carton</span>
                <input type="text" class="form-control" style="max-width:180px" placeholder="Code (optional)" name="items[${i}][carton_plan][0][name]">
                <span class="po-carton-lbl ms-2">Boxes</span>
                <input type="number" min="1" class="form-control" style="max-width:90px" placeholder="0" name="items[${i}][carton_plan][0][boxes]">
                <input type="hidden" name="items[${i}][carton_plan][0][units_per_box]" value="1">
            </div>
        </div>`;
    }

    /* ── Append card ── */
    function appendCard(skuId, skuLabel, retailPrice, mrpVal){
        document.getElementById('emptyState')?.remove();
        const list = document.getElementById('poItemsList');
        list.insertAdjacentHTML('beforeend', buildCard(skuId, skuLabel, retailPrice, mrpVal));
        idx++;
        updateNums(); updateCount(); recalc();
        const card = list.lastElementChild;
        card?.querySelector('.qty-input')?.focus();
    }

    function updateNums(){ document.querySelectorAll('#poItemsList .po-card .item-num').forEach((el,i) => el.textContent = i+1); }
    function updateCount(){ const n = document.querySelectorAll('#poItemsList .po-card').length; const el = document.getElementById('itemCountBadge'); if(el) el.textContent = n+' item'+(n!==1?'s':''); }

    /* ── Select2 AJAX ── */
    const $qa = $('#productQuickAdd');
    if($qa.length){
        $qa.select2({
            theme:'bootstrap-5', width:'100%',
            placeholder:'🔍  Search by product name or SKU code...',
            allowClear:true, minimumInputLength:1,
            ajax:{
                url:skuSearchUrl, dataType:'json', delay:250,
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data.results }),
                cache:true,
            },
        });
        $qa.on('select2:select', function(e){
            const d = e.params.data;
            skuMap[d.id] = d.text;
            appendCard(d.id, d.text, d.retail_price || 0, d.mrp || 0);
            $qa.val(null).trigger('change');
        });
    }

    /* ── Blank row ── */
    document.getElementById('addBlankRow')?.addEventListener('click', function(){
        appendCard('', '— Select Product —', 0);
        const list = document.getElementById('poItemsList');
        const card = list?.lastElementChild;
        if(!card) return;
        const i = idx - 1;
        const namB = card.querySelector('.po-pname b');
        const hid  = card.querySelector('.sku-id-input');
        if(!namB||!hid) return;
        const sel = document.createElement('select');
        sel.className = 'form-control form-control-sm mt-1';
        sel.name = `items[${i}][sku_id]`;
        sel.innerHTML = '<option value=""></option>';
        namB.parentNode.insertBefore(sel, namB.nextSibling);
        namB.textContent = '— Select Product —';
        hid.remove();
        $(sel).select2({
            theme:'bootstrap-5', width:'100%', placeholder:'Search SKU...', minimumInputLength:1,
            ajax:{ url:skuSearchUrl, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d.results}), cache:true },
        });
        $(sel).on('select2:select', function(e){
            const d = e.params.data;
            skuMap[d.id] = d.text;
            namB.textContent = d.text;
            if(d.retail_price){
                const mrp = card.querySelector('[name*="[mrp]"]');
                const sp  = card.querySelector('[name*="[sale_price]"]');
                if(mrp && !mrp.value) mrp.value = parseFloat(d.retail_price).toFixed(2);
                if(sp  && !sp.value)  sp.value  = parseFloat(d.retail_price).toFixed(2);
            }
            recalc();
        });
    });

    /* ── Events ── */
    document.getElementById('poItemsList').addEventListener('input', function(e){
        if(e.target.matches('.item-calc')) recalc();
    });
    document.getElementById('poItemsList').addEventListener('click', function(e){
        if(e.target.closest('.remove-item-btn')){
            e.target.closest('.po-card').remove();
            updateNums(); updateCount(); recalc();
            if(!document.querySelector('#poItemsList .po-card')){
                document.getElementById('poItemsList').insertAdjacentHTML('beforeend',
                    '<div class="po-empty" id="emptyState"><i class="bi bi-inbox"></i>Use the search bar above to add products</div>');
            }
        }
    });
    document.getElementById('other_charge_total')?.addEventListener('input', recalc);
    document.getElementById('paid_total')?.addEventListener('input', recalc);

    updateCount(); recalc();
})();
</script>
@endsection
