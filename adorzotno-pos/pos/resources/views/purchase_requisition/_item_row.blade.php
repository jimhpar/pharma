@php
    $stock = $item['current_stock'] ?? 0;
    $stockClass = $stock > 10 ? 'stock-ok' : ($stock > 0 ? 'stock-low' : 'stock-zero');
@endphp
<div class="pr-card" id="pr-item-{{ $idx }}">
    <div class="pr-card-hd">
        <div class="pr-num">{{ $idx + 1 }}</div>
        <div class="pr-pname">
            <b>{{ $item['_sku_text'] ?? 'SKU #' . $item['sku_id'] }}</b>
            <small>SKU ID: {{ $item['sku_id'] }}</small>
        </div>
        <span class="pr-stock-badge {{ $stockClass }} me-2">Current Stock: {{ $stock }}</span>
        @unless($isLocked)
        <button type="button" class="pr-delbtn" onclick="removePrItem({{ $idx }})" title="Remove"><i class="bi bi-x-lg" style="font-size:.7rem"></i></button>
        @endunless
    </div>
    <div class="pr-fields">
        <input type="hidden" name="items[{{ $idx }}][sku_id]" value="{{ $item['sku_id'] }}">
        <div class="pr-field req-field">
            <label>Requested Qty *</label>
            <input type="number" name="items[{{ $idx }}][requested_quantity]" class="form-control"
                   min="1" value="{{ $item['requested_quantity'] ?? 1 }}" {{ $isLocked ? 'readonly' : '' }} required>
        </div>
        <div class="pr-field">
            <label>Current Stock</label>
            <input type="number" name="items[{{ $idx }}][current_stock]" class="form-control"
                   value="{{ $item['current_stock'] ?? 0 }}" readonly>
        </div>
        <div class="pr-field">
            <label>Est. Unit Cost</label>
            <input type="number" name="items[{{ $idx }}][estimated_unit_cost]" class="form-control"
                   min="0" step="0.01" value="{{ $item['estimated_unit_cost'] ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>
        </div>
        <div class="pr-field" style="grid-column:span 2">
            <label>Note</label>
            <input type="text" name="items[{{ $idx }}][item_note]" class="form-control"
                   value="{{ $item['item_note'] ?? '' }}" {{ $isLocked ? 'readonly' : '' }} placeholder="Optional note…">
        </div>
    </div>
</div>
