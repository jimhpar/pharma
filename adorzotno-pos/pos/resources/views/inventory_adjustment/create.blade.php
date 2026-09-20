@extends('layouts.main')
@section('main.content')
@php
    $currentDocumentType = old('document_type', $documentType ?? $adjustment->document_type ?? \App\Models\InventoryAdjustment::TYPE_ADJUSTMENT);
    $showDirectionColumn = $currentDocumentType === \App\Models\InventoryAdjustment::TYPE_ADJUSTMENT;
    $isStockIssue = $currentDocumentType === \App\Models\InventoryAdjustment::TYPE_STOCK_ISSUE;
    $adjustmentItems = collect(old('items', [[
        'batch_id' => '',
        'sku_id' => '',
        'adjustment_type' => $showDirectionColumn ? 'increase' : ($isStockIssue ? 'decrease' : 'increase'),
        'batch_no' => '',
        'unit_cost' => 0,
        'expiry_date' => '',
        'quantity' => 1,
        'reason' => '',
    ]]));
    $batchOptions = $batches->map(function ($batch) {
        return [
            'id' => $batch->id,
            'branch_id' => $batch->warehouse?->branch_id,
            'warehouse_id' => $batch->warehouse_id,
            'sku_id' => $batch->sku_id,
            'batch_no' => $batch->batch_no,
            'purchase_price' => (float) ($batch->purchase_price ?? 0),
            'expiry_date' => optional($batch->expiry_date)->format('Y-m-d'),
            'available_quantity' => (int) ($batch->available_quantity ?? 0),
            'label' => ($batch->batch_no ?? 'N/A') . ' | ' . ($batch->sku?->display_name ?? 'N/A') . ' | WH: ' . ($batch->warehouse?->name ?? 'N/A') . ' | Stock: ' . (int) $batch->available_quantity,
        ];
    })->values();
    $warehouseOptions = $warehouses->map(function ($warehouse) {
        return [
            'id' => $warehouse->id,
            'branch_id' => $warehouse->branch_id,
            'name' => $warehouse->name,
            'branch_name' => $warehouse->branch?->name ?? 'N/A',
        ];
    })->values();
    $skuOptions = $skus->map(fn ($sku) => ['id' => $sku->id, 'label' => $sku->display_name])->values();
@endphp

<style>
    .field-auto-filled {
        background-color: #eef2ff !important;
        border-color: #a5b4fc !important;
        color: #3730a3 !important;
        cursor: default;
    }
    .item-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        transition: box-shadow .15s;
    }
    .item-card:hover { box-shadow: 0 2px 10px rgba(0,0,0,.08); }
    .item-card .card-body { padding: 1rem 1.1rem; }
    .item-card .item-row-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 3px;
    }
    .stock-pill {
        display: inline-block;
        font-size: 1rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        min-width: 48px;
        text-align: center;
    }
    .stock-ok   { background: #dcfce7; color: #166534; }
    .stock-warn { background: #fef9c3; color: #854d0e; }
    .stock-zero { background: #fee2e2; color: #991b1b; }
    .stock-na   { background: #f1f5f9; color: #94a3b8; }
    .remaining-display {
        font-size: 1rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        min-width: 48px;
        text-align: center;
        display: inline-block;
    }
    .remaining-ok   { background: #dcfce7; color: #166534; }
    .remaining-zero { background: #fef9c3; color: #854d0e; }
    .remaining-over { background: #fee2e2; color: #991b1b; }
    .remaining-na   { background: #f1f5f9; color: #94a3b8; }
    .summary-bar { background: #f8fafc; border-radius: 10px; padding: 12px 20px; border: 1px solid #e2e8f0; }
    .item-index-badge {
        width: 26px; height: 26px; border-radius: 50%;
        background: #e2e8f0; color: #475569;
        font-size: .75rem; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
</style>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>{{ $pageTitle ?? 'Stock Adjustment Create' }}</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventoryAdjustment.show') }}">Inventory Documents</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section id="multiple-column-form">
        <div class="row match-height">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h4 class="card-title mb-0">{{ $cardTitle ?? 'Adjustment Form' }}</h4>
                        @if ($isStockIssue)
                            <span class="badge bg-warning text-dark px-3 py-2" style="font-size:.85rem">
                                <i class="bi bi-arrow-down-circle me-1"></i> Stock Issue
                            </span>
                        @endif
                    </div>
                    <div class="card-content">
                        <div class="card-body">

                            {{-- Alerts --}}
                            @if ($currentDocumentType === \App\Models\InventoryAdjustment::TYPE_OPENING_STOCK)
                                <div class="alert alert-info d-flex gap-2 align-items-start">
                                    <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
                                    <div>
                                        <strong>Opening Stock Entry</strong><br>
                                        Use an existing batch to top up stock, or leave Batch blank and select a SKU to create a new opening-stock batch.
                                    </div>
                                </div>
                            @elseif ($isStockIssue)
                                <div class="alert alert-warning d-flex gap-2 align-items-start mb-4">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0" style="color:#92400e"></i>
                                    <div>
                                        <strong>Stock Issue — Inventory will be reduced</strong><br>
                                        Select a batch to see available stock. Issue quantity cannot exceed the available amount.
                                        Fields highlighted in <span style="background:#eef2ff;color:#3730a3;padding:1px 7px;border-radius:4px;font-weight:600;">blue</span> are auto-filled from the selected batch.
                                    </div>
                                </div>
                            @endif

                            <form class="form" method="post" action="{{ route('inventoryAdjustment.store') }}">
                                @csrf
                                <input type="hidden" name="document_type" value="{{ $currentDocumentType }}">

                                {{-- Header Fields --}}
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4 col-12">
                                        <label class="form-label fw-semibold">Document No</label>
                                        <input class="form-control @error('adjustment_no') is-invalid @enderror" name="adjustment_no" value="{{ old('adjustment_no', $adjustment->adjustment_no) }}" placeholder="Auto-generated">
                                        @error('adjustment_no')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('adjustment_date') is-invalid @enderror" name="adjustment_date" value="{{ old('adjustment_date', $adjustment->adjustment_date) }}">
                                        @error('adjustment_date')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                                        <select class="form-control @error('branch_id') is-invalid @enderror" name="branch_id" id="inventoryDocumentBranchId">
                                            <option value="">— Select Branch —</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ (string) old('branch_id', $adjustment->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label class="form-label fw-semibold">Warehouse <span class="text-danger">*</span></label>
                                        <select class="form-control @error('warehouse_id') is-invalid @enderror" name="warehouse_id" id="inventoryDocumentWarehouseId"></select>
                                        @error('warehouse_id')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label class="form-label fw-semibold">Note / Remarks</label>
                                        <textarea class="form-control @error('note') is-invalid @enderror" name="note" rows="2" placeholder="Optional note for this document...">{{ old('note', $adjustment->note) }}</textarea>
                                        @error('note')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                    </div>
                                </div>

                                {{-- Items Header --}}
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-list-ul"></i> Items
                                        <span class="badge bg-secondary" id="itemCountBadge">{{ $adjustmentItems->count() }}</span>
                                    </h5>
                                    <button type="button" class="btn btn-primary btn-sm px-3" id="addAdjustmentRow">
                                        <i class="bi bi-plus-lg me-1"></i> Add Item
                                    </button>
                                </div>

                                @error('items')
                                    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i><strong>{{ $message }}</strong></div>
                                @enderror

                                {{-- Items Cards --}}
                                <div id="adjustmentItemsBody">
                                    @foreach ($adjustmentItems as $index => $item)
                                    <div class="item-card card mb-3">
                                        <div class="card-body">
                                            {{-- Hidden adjustment_type --}}
                                            @if (!$showDirectionColumn)
                                                <input type="hidden" name="items[{{ $index }}][adjustment_type]" value="{{ $isStockIssue ? 'decrease' : 'increase' }}">
                                            @endif

                                            {{-- Row 1: Batch + SKU + Direction + Remove --}}
                                            <div class="row g-2 align-items-end mb-2">
                                                <div class="col-12 col-md-5">
                                                    <div class="item-row-label">Batch <span class="text-danger">*</span></div>
                                                    <select class="form-control batch-select @error("items.$index.batch_id") is-invalid @enderror" name="items[{{ $index }}][batch_id]" data-selected-value="{{ $item['batch_id'] ?? '' }}">
                                                        <option value="">— Select Batch —</option>
                                                    </select>
                                                    @error("items.$index.batch_id")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                                <div class="col-12 col-md-5">
                                                    <div class="item-row-label">SKU / Product</div>
                                                    <select class="form-control @error("items.$index.sku_id") is-invalid @enderror" name="items[{{ $index }}][sku_id]">
                                                        <option value="">— Select SKU —</option>
                                                        @foreach ($skus as $sku)
                                                            <option value="{{ $sku->id }}" {{ (string) ($item['sku_id'] ?? '') === (string) $sku->id ? 'selected' : '' }}>{{ $sku->display_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("items.$index.sku_id")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                                @if ($showDirectionColumn)
                                                <div class="col-6 col-md-1">
                                                    <div class="item-row-label">Direction</div>
                                                    <select class="form-control @error("items.$index.adjustment_type") is-invalid @enderror" name="items[{{ $index }}][adjustment_type]">
                                                        <option value="increase" {{ ($item['adjustment_type'] ?? '') === 'increase' ? 'selected' : '' }}>Increase</option>
                                                        <option value="decrease" {{ ($item['adjustment_type'] ?? '') === 'decrease' ? 'selected' : '' }}>Decrease</option>
                                                    </select>
                                                </div>
                                                @endif
                                                <div class="col-auto ms-auto d-flex align-items-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger removeAdjustmentRow" title="Remove item">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Row 2: Auto-filled info + Qty + Remaining + Reason --}}
                                            <div class="row g-2 align-items-end">
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="item-row-label">Batch No</div>
                                                    <input class="form-control @error("items.$index.batch_no") is-invalid @enderror" name="items[{{ $index }}][batch_no]" value="{{ $item['batch_no'] ?? '' }}" placeholder="Batch No">
                                                    @error("items.$index.batch_no")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="item-row-label">Unit Cost</div>
                                                    <input type="number" step="0.01" min="0" class="form-control @error("items.$index.unit_cost") is-invalid @enderror" name="items[{{ $index }}][unit_cost]" value="{{ $item['unit_cost'] ?? 0 }}">
                                                    @error("items.$index.unit_cost")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="item-row-label">Expiry Date</div>
                                                    <input type="date" class="form-control @error("items.$index.expiry_date") is-invalid @enderror" name="items[{{ $index }}][expiry_date]" value="{{ $item['expiry_date'] ?? '' }}">
                                                    @error("items.$index.expiry_date")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                                @if ($isStockIssue)
                                                <div class="col-6 col-sm-3 col-md-1 text-center">
                                                    <div class="item-row-label">In Stock</div>
                                                    <span class="stock-pill stock-na available-stock-display">—</span>
                                                </div>
                                                @endif
                                                <div class="col-6 col-sm-3 col-md-2">
                                                    <div class="item-row-label">{{ $isStockIssue ? 'Issue Qty' : 'Qty' }} <span class="text-danger">*</span></div>
                                                    <input type="number" min="1" class="form-control qty-input @error("items.$index.quantity") is-invalid @enderror" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}">
                                                    @error("items.$index.quantity")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                                @if ($isStockIssue)
                                                <div class="col-6 col-sm-3 col-md-1 text-center">
                                                    <div class="item-row-label">After Issue</div>
                                                    <span class="remaining-display remaining-na remaining-stock-display">—</span>
                                                </div>
                                                @endif
                                                <div class="col-12 col-sm col-md">
                                                    <div class="item-row-label">Reason</div>
                                                    <input class="form-control @error("items.$index.reason") is-invalid @enderror" name="items[{{ $index }}][reason]" value="{{ $item['reason'] ?? '' }}" placeholder="e.g. Damaged, Expired, Lost...">
                                                    @error("items.$index.reason")<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                {{-- Summary Bar --}}
                                <div class="summary-bar d-flex align-items-center gap-4 mt-1 mb-3 flex-wrap">
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.04em">Total Items</div>
                                        <strong id="totalItemsDisplay">{{ $adjustmentItems->count() }}</strong>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.04em">Total {{ $isStockIssue ? 'Issue' : '' }} Qty</div>
                                        <strong id="totalQtyDisplay">{{ $adjustmentItems->sum('quantity') }}</strong>
                                    </div>
                                    @if ($isStockIssue)
                                    <div class="ms-auto">
                                        <span id="overIssueWarning" class="text-danger fw-semibold d-none">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Some items exceed available stock
                                        </span>
                                    </div>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-1">
                                    <a href="{{ route('inventoryAdjustment.show') }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-check2-circle me-1"></i> {{ $submitLabel ?? 'Submit' }}
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
    const inventoryDocumentType = @json($currentDocumentType);
    const inventoryBatchOptions = @json($batchOptions);
    const inventoryWarehouseOptions = @json($warehouseOptions);
    const inventorySkuOptions = @json($skuOptions);
    const initialWarehouseId = @json(old('warehouse_id', $adjustment->warehouse_id));
    let adjustmentItemIndex = {{ $adjustmentItems->count() }};

    function buildWarehouseOptions(branchId, selectedId = '') {
        let options = '<option value="">— Select Warehouse —</option>';
        inventoryWarehouseOptions
            .filter(w => String(w.branch_id) === String(branchId))
            .forEach(w => {
                const selected = String(selectedId) === String(w.id) ? 'selected' : '';
                options += `<option value="${w.id}" ${selected}>${w.name} (${w.branch_name})</option>`;
            });
        return options;
    }

    function syncWarehouseDropdown(selectedId = '') {
        const branchId = $('#inventoryDocumentBranchId').val();
        const $ws = $('#inventoryDocumentWarehouseId');
        if (!branchId) { $ws.html('<option value="">— Select Warehouse —</option>').trigger('change'); return; }
        $ws.html(buildWarehouseOptions(branchId, selectedId)).trigger('change');
        if (selectedId && !$ws.val()) $ws.val('');
    }

    function buildSkuOptions(selectedId = '') {
        let options = '<option value="">— Select SKU —</option>';
        inventorySkuOptions.forEach(sku => {
            const selected = String(selectedId) === String(sku.id) ? 'selected' : '';
            options += `<option value="${sku.id}" ${selected}>${sku.label}</option>`;
        });
        return options;
    }

    function buildBatchOptions(selectedId = '') {
        const branchId = $('#inventoryDocumentBranchId').val();
        const warehouseId = $('#inventoryDocumentWarehouseId').val();
        let options = '<option value="">— Select Batch —</option>';
        inventoryBatchOptions
            .filter(b => String(b.branch_id) === String(branchId) && String(b.warehouse_id) === String(warehouseId))
            .forEach(b => {
                const selected = String(selectedId) === String(b.id) ? 'selected' : '';
                options += `<option value="${b.id}" ${selected}>${b.label}</option>`;
            });
        return options;
    }

    function refreshBatchSelectors() {
        $('.batch-select').each(function () {
            const selectedId = $(this).data('selected-value') || $(this).val() || '';
            $(this).html(buildBatchOptions(selectedId)).trigger('change');
            if (selectedId && !$(this).val()) {
                $(this).data('selected-value', '');
                $(this).val('');
            }
        });
    }

    function setAutoFilled($input, value) {
        $input.val(value).addClass('field-auto-filled').prop('readonly', true);
    }

    function clearAutoFilled($input) {
        $input.removeClass('field-auto-filled').prop('readonly', false);
    }

    function setStockPill($el, available) {
        $el.removeClass('stock-ok stock-warn stock-zero stock-na');
        if (available === null) { $el.addClass('stock-na').text('—'); return; }
        $el.text(available);
        if (available <= 0)  $el.addClass('stock-zero');
        else if (available < 10) $el.addClass('stock-warn');
        else $el.addClass('stock-ok');
    }

    function setRemainingPill($el, remaining) {
        $el.removeClass('remaining-ok remaining-zero remaining-over remaining-na');
        if (remaining === null) { $el.addClass('remaining-na').text('—'); return; }
        $el.text(remaining);
        if (remaining < 0)     $el.addClass('remaining-over');
        else if (remaining === 0) $el.addClass('remaining-zero');
        else $el.addClass('remaining-ok');
    }

    function updateRemainingStock($card) {
        if (inventoryDocumentType !== 'stock_issue') return;
        const available = $card.data('available-qty');
        const qty = parseInt($card.find('.qty-input').val() || 0);
        const $remaining = $card.find('.remaining-stock-display');
        const $qtyInput = $card.find('.qty-input');

        if (available === undefined || available === null) {
            setRemainingPill($remaining, null);
            $qtyInput.removeClass('is-invalid');
        } else {
            const remaining = available - qty;
            setRemainingPill($remaining, remaining);
            if (remaining < 0) $qtyInput.addClass('is-invalid');
            else $qtyInput.removeClass('is-invalid');
        }
        updateSummary();
    }

    function updateSummary() {
        let totalQty = 0, hasOverIssue = false, rowCount = 0;
        $('#adjustmentItemsBody .item-card').each(function () {
            rowCount++;
        });
        $('input.qty-input').each(function () {
            totalQty += parseInt($(this).val() || 0);
            if ($(this).hasClass('is-invalid')) hasOverIssue = true;
        });
        $('#totalQtyDisplay').text(totalQty);
        $('#totalItemsDisplay').text(rowCount);
        $('#itemCountBadge').text(rowCount);
        if (inventoryDocumentType === 'stock_issue') {
            $('#overIssueWarning').toggleClass('d-none', !hasOverIssue);
        }
    }

    function populateRowFromBatch($card, batchId) {
        const $batchNo   = $card.find('input[name$="[batch_no]"]');
        const $unitCost  = $card.find('input[name$="[unit_cost]"]');
        const $expiry    = $card.find('input[name$="[expiry_date]"]');
        const $skuSelect = $card.find('select[name$="[sku_id]"]');
        const $stockPill = $card.find('.available-stock-display');

        if (!batchId) {
            clearAutoFilled($batchNo);
            clearAutoFilled($unitCost);
            clearAutoFilled($expiry);
            $card.removeData('available-qty');
            setStockPill($stockPill, null);
            setRemainingPill($card.find('.remaining-stock-display'), null);
            $card.find('.qty-input').removeClass('is-invalid');
            return;
        }

        const batch = inventoryBatchOptions.find(b => String(b.id) === String(batchId));
        if (!batch) return;

        $skuSelect.val(String(batch.sku_id));
        setAutoFilled($batchNo, batch.batch_no || '');
        setAutoFilled($unitCost, batch.purchase_price ?? 0);
        setAutoFilled($expiry, batch.expiry_date || '');

        if (inventoryDocumentType === 'stock_issue') {
            $card.data('available-qty', batch.available_quantity);
            setStockPill($stockPill, batch.available_quantity);
            updateRemainingStock($card);
        }
    }

    function buildNewCard() {
        const hiddenType = inventoryDocumentType === 'adjustment' ? '' :
            `<input type="hidden" name="items[${adjustmentItemIndex}][adjustment_type]" value="${inventoryDocumentType === 'stock_issue' ? 'decrease' : 'increase'}">`;

        const directionCol = inventoryDocumentType === 'adjustment' ? `
            <div class="col-6 col-sm-3 col-md-1">
                <div class="item-row-label">Direction</div>
                <select class="form-control" name="items[${adjustmentItemIndex}][adjustment_type]">
                    <option value="increase">Increase</option>
                    <option value="decrease">Decrease</option>
                </select>
            </div>` : '';

        const stockIssueAvail = inventoryDocumentType === 'stock_issue' ? `
            <div class="col-6 col-sm-3 col-md-1 text-center">
                <div class="item-row-label">In Stock</div>
                <span class="stock-pill stock-na available-stock-display">—</span>
            </div>` : '';

        const stockIssueRemain = inventoryDocumentType === 'stock_issue' ? `
            <div class="col-6 col-sm-3 col-md-1 text-center">
                <div class="item-row-label">After Issue</div>
                <span class="remaining-display remaining-na remaining-stock-display">—</span>
            </div>` : '';

        const qtyLabel = inventoryDocumentType === 'stock_issue' ? 'Issue Qty' : 'Qty';

        return `
        <div class="item-card card mb-3">
            <div class="card-body">
                ${hiddenType}
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-12 col-md-5">
                        <div class="item-row-label">Batch <span class="text-danger">*</span></div>
                        <select class="form-control batch-select" name="items[${adjustmentItemIndex}][batch_id]" data-selected-value="">${buildBatchOptions()}</select>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="item-row-label">SKU / Product</div>
                        <select class="form-control" name="items[${adjustmentItemIndex}][sku_id]">${buildSkuOptions()}</select>
                    </div>
                    ${directionCol}
                    <div class="col-auto ms-auto d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger removeAdjustmentRow" title="Remove item">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="item-row-label">Batch No</div>
                        <input class="form-control" name="items[${adjustmentItemIndex}][batch_no]" placeholder="Batch No">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="item-row-label">Unit Cost</div>
                        <input type="number" step="0.01" min="0" class="form-control" name="items[${adjustmentItemIndex}][unit_cost]" value="0">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="item-row-label">Expiry Date</div>
                        <input type="date" class="form-control" name="items[${adjustmentItemIndex}][expiry_date]">
                    </div>
                    ${stockIssueAvail}
                    <div class="col-6 col-sm-3 col-md-2">
                        <div class="item-row-label">${qtyLabel} <span class="text-danger">*</span></div>
                        <input type="number" min="1" class="form-control qty-input" name="items[${adjustmentItemIndex}][quantity]" value="1">
                    </div>
                    ${stockIssueRemain}
                    <div class="col-12 col-sm col-md">
                        <div class="item-row-label">Reason</div>
                        <input class="form-control" name="items[${adjustmentItemIndex}][reason]" placeholder="e.g. Damaged, Expired, Lost...">
                    </div>
                </div>
            </div>
        </div>`;
    }

    $(document).on('click', '#addAdjustmentRow', function () {
        $('#adjustmentItemsBody').append(buildNewCard());
        adjustmentItemIndex++;
        updateSummary();
    });

    $(document).on('click', '.removeAdjustmentRow', function () {
        if ($('#adjustmentItemsBody .item-card').length <= 1) return;
        $(this).closest('.item-card').remove();
        updateSummary();
    });

    $(document).on('change', '#inventoryDocumentBranchId', function () {
        syncWarehouseDropdown();
        refreshBatchSelectors();
    });

    $(document).on('change', '#inventoryDocumentWarehouseId', function () {
        refreshBatchSelectors();
    });

    $(document).on('change', '.batch-select', function () {
        const $card = $(this).closest('.item-card');
        const batchId = $(this).val();
        $(this).data('selected-value', batchId);
        populateRowFromBatch($card, batchId);
    });

    $(document).on('input change', '.qty-input', function () {
        updateRemainingStock($(this).closest('.item-card'));
    });

    $(document).ready(function () {
        syncWarehouseDropdown(initialWarehouseId);
        refreshBatchSelectors();

        $('.batch-select').each(function () {
            populateRowFromBatch($(this).closest('.item-card'), $(this).data('selected-value'));
        });

        updateSummary();
    });
</script>
@endsection
