@extends('layouts.main')

@php
    $type = old('type', $product->type);
    $singleSku = $product->sku->first();
    $skuById = $product->sku->keyBy('id');
    $flatVariations = $variations->flatten(1)->keyBy('id');
    $variationTypes = $variations->keys()->values();
    $variationValues = $variations->map(fn ($items) => $items->map(fn ($item) => ['id' => $item->id, 'value' => $item->value])->values())->toArray();
    $removedVariantIds = collect(old('remove_variant_ids', []))
        ->filter(fn ($id) => $id !== null && $id !== '')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
    $showSingleProductDiv = in_array($type, ['Single', 'Combo', 'Service'], true);
    $showVariationProductDiv = $type === 'Variation';
    $variantRows = old('variants');

    if ($variantRows === null) {
        $variantRows = $product->sku->map(fn ($sku) => [
            'sku_id' => $sku->id,
            'sku_code' => $sku->sku_code,
            'barcode' => $sku->barcode,
            'cost_price' => $sku->cost_price,
            'retail_price' => $sku->retail_price,
            'wholesale_price' => $sku->wholesale_price,
            'minimum_selling_price' => $sku->minimum_selling_price,
            'online_price' => $sku->online_price,
            'weight' => $sku->weight,
            'units_per_strip' => $sku->units_per_strip ?? 1,
            'medicine_unit_price' => $sku->medicine_unit_price,
            'rating' => $sku->rating,
            'dosage_details' => $sku->dosage_details,
            'track_stock' => $sku->track_stock,
            'track_batch' => $sku->track_batch,
            'track_expiry' => $sku->track_expiry,
            'status' => $sku->status,
            'variation_value_ids' => $sku->variationRelation->pluck('variation_id')->values()->all(),
        ])->values()->all();
    }

    $variantCount = count($variantRows);
    $nextVariantIndex = collect(array_keys($variantRows))
        ->map(fn ($key) => is_numeric($key) ? (int) $key : null)
        ->filter(fn ($key) => $key !== null)
        ->whenEmpty(fn ($collection) => $collection->push(count($variantRows) - 1))
        ->max() + 1;
@endphp

@section('header.css')
<style>
    .product-form-page {
        --pf-surface: #ffffff;
        --pf-surface-alt: #f8fbff;
        --pf-border: #dce3ef;
        --pf-text: #172033;
        --pf-muted: #6b7280;
        --pf-input-bg: #ffffff;
        --pf-input-border: #ced4da;
    }

    html[data-bs-theme="dark"] .product-form-page {
        --pf-surface: #111827;
        --pf-surface-alt: #0f172a;
        --pf-border: rgba(148, 163, 184, 0.2);
        --pf-text: #e5edf8;
        --pf-muted: #9fb0c5;
        --pf-input-bg: #0b1220;
        --pf-input-border: rgba(148, 163, 184, 0.24);
    }

    .product-form-page,
    .product-form-page label,
    .product-form-page .card-title,
    .product-form-page .section-title,
    .product-form-page h3,
    .product-form-page h4,
    .product-form-page h5,
    .product-form-page strong {
        color: var(--pf-text);
    }

    .product-form-page .text-muted,
    .product-form-page .breadcrumb-item,
    .product-form-page .small,
    .product-form-page .variant-summary {
        color: var(--pf-muted) !important;
    }

    .product-form-page .card,
    .product-form-page .card-header,
    .product-form-page .card-body {
        background: var(--pf-surface);
        color: var(--pf-text);
    }

    .product-form-page .card {
        border: 1px solid var(--pf-border);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    }

    html[data-bs-theme="dark"] .product-form-page .card {
        box-shadow: 0 22px 40px rgba(2, 6, 23, 0.28);
    }

    .section-card {
        border: 1px solid var(--pf-border);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background: var(--pf-surface);
    }

    .section-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .variant-card {
        border: 1px solid var(--pf-border);
    }

    .variant-card.is-collapsed .variant-toggle-icon {
        transform: rotate(-90deg);
    }

    .variant-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .variant-summary {
        font-size: 0.92rem;
        margin-top: 0.2rem;
    }

    .variant-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .variant-toggle-icon {
        transition: transform 0.2s ease;
    }

    .product-form-page .variation-pair {
        border-color: var(--pf-border) !important;
        background: var(--pf-surface-alt);
    }

    .product-form-page .form-control,
    .product-form-page .form-select,
    .product-form-page textarea,
    .product-form-page select {
        background: var(--pf-input-bg);
        color: var(--pf-text);
        border-color: var(--pf-input-border);
    }

    .product-form-page .form-control:focus,
    .product-form-page .form-select:focus,
    .product-form-page textarea:focus,
    .product-form-page select:focus {
        background: var(--pf-input-bg);
        color: var(--pf-text);
    }

    .product-form-page .form-check-input {
        border-color: var(--pf-input-border);
        cursor: pointer;
    }

    .product-form-page .form-check-input:not(:checked) {
        background-color: var(--pf-input-bg);
    }

    .product-form-page .form-check-input:checked {
        background-color: #435ebe;
        border-color: #435ebe;
    }

    .product-form-page .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(67, 94, 190, 0.2);
    }

    .product-form-page .checkbox-stack {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 38px;
    }

    .product-form-page .checkbox-stack .form-check-label {
        margin-bottom: 0;
        cursor: pointer;
    }

    #singleProductDiv,
    #variationProductDiv {
        display: none;
    }
</style>
@endsection

@section('main.content')
<div class="page-heading product-form-page">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Product Edit</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Product Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Product Edit Form</h4></div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="productEditForm" method="post" action="{{ route('product.update', $product->id) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="section-card">
                        <div class="section-title">General Details</div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Name</label>
                                <input class="form-control" name="name" value="{{ old('name', $product->name) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Slug</label>
                                <input class="form-control" name="slug" value="{{ old('slug', $product->slug) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Type</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="Single" {{ $type === 'Single' ? 'selected' : '' }}>Standard</option>
                                    <option value="Variation" {{ $type === 'Variation' ? 'selected' : '' }}>Variant</option>
                                    <option value="Combo" {{ $type === 'Combo' ? 'selected' : '' }}>Combo</option>
                                    <option value="Service" {{ $type === 'Service' ? 'selected' : '' }}>Service</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label>Category <span class="text-danger">*</span></label>
                                @include('product.partials.category-picker', [
                                    'pickerId' => 'catPicker',
                                    'options'  => $categories,
                                    'selected' => (array) old('categories',
                                        $product->categories->pluck('id')->toArray() ?: [$product->category_id]
                                    ),
                                ])
                                @error('categories')
                                    <div class="text-danger small mt-1"><strong>{{ $message }}</strong></div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Brand</label>
                                <select class="form-control" name="brand">
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ (string) old('brand', $product->brand_id) === (string) $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Unit</label>
                                <select class="form-control" name="unit_id">
                                    <option value="">Select Unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" {{ (string) old('unit_id', $product->unit_id) === (string) $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }}{{ $unit->short_name ? ' (' . $unit->short_name . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>VAT Rule</label>
                                <select class="form-control" name="tax_rule_id">
                                    <option value="">Select VAT Rule</option>
                                    @foreach ($taxRules as $taxRule)
                                        <option value="{{ $taxRule->id }}" {{ (string) old('tax_rule_id', $product->tax_rule_id) === (string) $taxRule->id ? 'selected' : '' }}>
                                            {{ $taxRule->name }} ({{ $taxRule->rate_percent }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Thumbnail Image</label>
                                <input class="form-control" type="file" name="thumbnail_image">
                                @if ($product->thumbnail_image)
                                    <img src="{{ url($product->thumbnail_image) }}" alt="" class="mt-2 rounded" style="width:100px;height:100px;object-fit:cover;">
                                @endif
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>SEO Title</label>
                                <input class="form-control" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Short Description</label>
                                <textarea class="form-control product-rich-editor" rows="4" name="short_description">{{ old('short_description', $product->short_description) }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Long Description</label>
                                <textarea class="form-control product-rich-editor" rows="4" name="long_description">{{ old('long_description', $product->long_description) }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>SEO Description</label>
                                <textarea class="form-control" rows="3" name="seo_description">{{ old('seo_description', $product->seo_description) }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Product Warnings</label>
                                <textarea class="form-control" rows="3" name="warnings">{{ old('warnings', $product->productWarnings->pluck('warning')->implode("\n")) }}</textarea>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Featured</label>
                                <input type="hidden" name="is_featured" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_featured">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Popular</label>
                                <input type="hidden" name="is_popular" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular" value="1" {{ old('is_popular', $product->is_popular) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_popular">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Online Enabled</label>
                                <input type="hidden" name="is_online_enabled" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_online_enabled" name="is_online_enabled" value="1" {{ old('is_online_enabled', $product->is_online_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_online_enabled">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">POS Enabled</label>
                                <input type="hidden" name="is_pos_enabled" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_pos_enabled" name="is_pos_enabled" value="1" {{ old('is_pos_enabled', $product->is_pos_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_pos_enabled">Enabled</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Optional branch-wise prices can be configured after saving the product from the `Branch Prices` module.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="singleProductDiv" class="section-card" style="display: {{ $showSingleProductDiv ? 'block' : 'none' }};">
                        <div class="section-title">SKU Details</div>
                        <div class="alert alert-info d-none" id="serviceTypeNote">
                            Service products are treated as non-stock items. Stock, batch, and expiry tracking will be turned off automatically.
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>SKU Code</label>
                                <div class="input-group">
                                    <input class="form-control" id="single_sku_code" name="sku_code" value="{{ old('sku_code', $singleSku?->sku_code) }}">
                                    <button class="btn btn-outline-primary" type="button" onclick="generateCode('single_sku_code')">Generate</button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Barcode</label>
                                <div class="input-group">
                                    <input class="form-control" id="single_barcode" name="barcode" value="{{ old('barcode', $singleSku?->barcode) }}">
                                    <button class="btn btn-outline-primary" type="button" onclick="generateCode('single_barcode')">Generate</button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>SKU Status</label>
                                <select class="form-control" name="sku_status">
                                    <option value="active" {{ old('sku_status', $singleSku?->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('sku_status', $singleSku?->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            {{-- ── Pricing ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Pricing</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Cost Price <span class="text-muted small">(TP)</span></label>
                                <input class="form-control" type="number" min="0" step="0.01" name="cost_price" value="{{ old('cost_price', $singleSku?->cost_price) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>MRP</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="retail_price" value="{{ old('retail_price', $singleSku?->retail_price) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Sale Price <span class="text-muted small">(POS default)</span></label>
                                <input class="form-control" type="number" min="0" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" placeholder="= MRP if blank">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Min Selling Price</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="minimum_selling_price" value="{{ old('minimum_selling_price', $singleSku?->minimum_selling_price) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Online Price</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="online_price" value="{{ old('online_price', $singleSku?->online_price) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Wholesale Price</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="wholesale_price" value="{{ old('wholesale_price', $singleSku?->wholesale_price) }}" placeholder="0.00">
                            </div>

                            {{-- ── Discount ── --}}
                            <div class="col-md-6 mb-3">
                                <label>Default Discount Type</label>
                                <select class="form-control no-select2" name="default_discount_type">
                                    <option value="">No Discount</option>
                                    <option value="percent" {{ old('default_discount_type', $product->default_discount_type) === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="amount"  {{ old('default_discount_type', $product->default_discount_type) === 'amount'  ? 'selected' : '' }}>Fixed Amount (৳)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Discount Value</label>
                                <div class="input-group">
                                    <input class="form-control" type="number" step="0.01" min="0" name="default_discount_value"
                                        value="{{ old('default_discount_value', $product->default_discount_value) }}" placeholder="0">
                                    <span class="input-group-text">% / ৳</span>
                                </div>
                                @if($product->sale_price && $product->default_discount_value)
                                @php
                                    $finalPrice = $product->default_discount_type === 'percent'
                                        ? $product->sale_price * (1 - $product->default_discount_value / 100)
                                        : $product->sale_price - $product->default_discount_value;
                                @endphp
                                <small class="text-success fw-semibold">Final: ৳{{ number_format($finalPrice, 2) }}</small>
                                @endif
                            </div>

                            {{-- ── Pharma Info ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Pharma Info</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Dosage Form</label>
                                <input class="form-control" type="text" name="dosage_form"
                                    value="{{ old('dosage_form', $product->dosage_form) }}" placeholder="e.g. Tablet, Capsule, Injection, Syrup">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Strength</label>
                                <input class="form-control" type="text" name="strength"
                                    value="{{ old('strength', $product->strength) }}" placeholder="e.g. 500mg, 45.5mg/2ml">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Coating Type <span class="text-muted small">(optional)</span></label>
                                <input class="form-control" type="text" name="coating_type"
                                    value="{{ old('coating_type', $product->coating_type) }}" placeholder="e.g. Film-coated">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Dosage Details</label>
                                <textarea class="form-control" rows="2" name="dosage_details">{{ old('dosage_details', $singleSku?->dosage_details) }}</textarea>
                            </div>
                            <div class="col-md-3 mb-3 medicine-pack-fields">
                                <label>Tablets / Capsules Per Strip</label>
                                <input class="form-control units-per-strip" type="number" min="1" step="1" name="units_per_strip" value="{{ old('units_per_strip', $singleSku?->units_per_strip ?? 1) }}">
                            </div>
                            <div class="col-md-3 mb-3 medicine-pack-fields">
                                <label>Per Medicine Price</label>
                                <input class="form-control medicine-unit-price" type="number" min="0" step="0.01" name="medicine_unit_price" value="{{ old('medicine_unit_price', $singleSku?->medicine_unit_price) }}" placeholder="0.00">
                            </div>

                            {{-- ── Other ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Other</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Weight (g)</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="weight" value="{{ old('weight', $singleSku?->weight) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Rating</label>
                                <input class="form-control" type="number" min="0" max="5" step="0.1" name="rating" value="{{ old('rating', $singleSku?->rating) }}" placeholder="0 - 5">
                            </div>

                            {{-- ── Tracking ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Stock Tracking</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Track Stock</label>
                                <input type="hidden" name="track_stock" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="track_stock" name="track_stock" value="1" {{ old('track_stock', $singleSku?->track_stock ?? 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="track_stock">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Track Batch</label>
                                <input type="hidden" name="track_batch" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="track_batch" name="track_batch" value="1" {{ old('track_batch', $singleSku?->track_batch) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="track_batch">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Track Expiry</label>
                                <input type="hidden" name="track_expiry" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="track_expiry" name="track_expiry" value="1" {{ old('track_expiry', $singleSku?->track_expiry) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="track_expiry">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Add Images</label>
                                <input class="form-control" type="file" name="single_product_images[]" multiple accept="image/*">
                            </div>
                        </div>

                        @if ($singleSku && $singleSku->images->isNotEmpty())
                            <div class="row">
                                @foreach ($singleSku->images as $image)
                                    <div class="col-md-2 mb-3">
                                        <img src="{{ url($image->image_path) }}" alt="" class="img-fluid rounded mb-2" style="height:100px;object-fit:cover;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="delete_single_image_ids[]" value="{{ $image->id }}" id="single-image-{{ $image->id }}" {{ in_array((string) $image->id, collect(old('delete_single_image_ids', []))->map(fn ($id) => (string) $id)->all(), true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="single-image-{{ $image->id }}">Remove</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div id="variationProductDiv" class="section-card" style="display: {{ $showVariationProductDiv ? 'block' : 'none' }};">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <div>
                                <h5 class="mb-0">Variant Details</h5>
                                <div class="small text-muted">{{ $variantCount }} variant{{ $variantCount === 1 ? '' : 's' }} loaded. Edit the cards below or add a new one.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="expand-all-variants">Expand All</button>
                                <button type="button" class="btn btn-outline-secondary" id="collapse-all-variants">Collapse All</button>
                                <button type="button" class="btn btn-primary" id="add-variant">Add New Variant</button>
                            </div>
                        </div>

                        <div id="removed-variant-ids">
                            @foreach ($removedVariantIds as $removedVariantId)
                                <input type="hidden" name="remove_variant_ids[]" value="{{ $removedVariantId }}">
                            @endforeach
                        </div>
                        <div id="variants" data-next-index="{{ $nextVariantIndex }}">
                            @if ($variantCount === 0)
                                <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" id="empty-variant-state">
                                    <div>No variant SKU exists yet for this product. Add one now to start editing variant prices, SKU codes, and variation values.</div>
                                    <button type="button" class="btn btn-primary trigger-add-variant">Add First Variant</button>
                                </div>
                            @endif
                            @foreach ($variantRows as $index => $row)
                                @php
                                    $selectedIds = collect($row['variation_value_ids'] ?? [])->values();
                                    $selectedModels = $selectedIds->map(fn ($id) => $flatVariations->get((int) $id))->filter()->values();
                                    $selectedTypes = collect($row['variation_type_keys'] ?? [])->values();
                                    $pairCount = max($selectedIds->count(), $selectedTypes->count(), 1);
                                    $pairRows = collect();

                                    for ($pairIndex = 0; $pairIndex < $pairCount; $pairIndex++) {
                                        $selectedId = $selectedIds->get($pairIndex);
                                        $selectedModel = $selectedId !== null && $selectedId !== ''
                                            ? $flatVariations->get((int) $selectedId)
                                            : null;

                                        $pairRows->push([
                                            'type' => $selectedTypes->get($pairIndex) ?? $selectedModel?->type ?? '',
                                            'value_id' => $selectedModel?->id ?? ($selectedId !== null && $selectedId !== '' ? (int) $selectedId : null),
                                        ]);
                                    }
                                    $images = !empty($row['sku_id']) && $skuById->has((int) $row['sku_id']) ? $skuById[(int) $row['sku_id']]->images : collect();
                                    $deletedIds = collect($row['delete_image_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
                                    $summaryText = collect([
                                        $selectedModels->pluck('value')->filter()->implode(', '),
                                        !empty($row['sku_code']) ? 'SKU: ' . $row['sku_code'] : null,
                                        isset($row['retail_price']) && $row['retail_price'] !== '' ? 'Retail: ' . $row['retail_price'] : null,
                                        !empty($row['status']) ? 'Status: ' . ucfirst($row['status']) : null,
                                    ])->filter()->implode(' | ');
                                @endphp
                                <div class="card mb-3 variant-card {{ ($errors->any() || $loop->first) ? '' : 'is-collapsed' }}" data-index="{{ $index }}">
                                    <div class="card-body">
                                        <div class="variant-header mb-3">
                                            <div class="flex-grow-1">
                                                <strong class="variant-title">Variant {{ $loop->iteration }}</strong>
                                                <div class="variant-summary">{{ $summaryText ?: 'No values selected yet' }}</div>
                                            </div>
                                            <div class="variant-actions">
                                                <button type="button" class="btn btn-outline-primary btn-sm toggle-variant">Hide / Show Variant</button>
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-variant">Remove Variant</button>
                                                <span class="variant-toggle-icon">&#9662;</span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="variants[{{ $index }}][sku_id]" value="{{ $row['sku_id'] ?? '' }}">
                                        <div class="variant-body" @if (!($errors->any() || $loop->first)) style="display:none;" @endif>
                                            <div class="variation-pairs">
                                                @foreach ($pairRows as $pairRow)
                                                    <div class="row border rounded p-2 mb-2 variation-pair">
                                                        <div class="col-md-5 mb-2">
                                                            <label>Variation Type</label>
                                                            <select class="form-control variation-type" name="variants[{{ $index }}][variation_type_keys][]">
                                                                <option value="">Select Variation Type</option>
                                                                @foreach ($variationTypes as $variationType)
                                                                    <option value="{{ $variationType }}" {{ ($pairRow['type'] ?? '') === $variationType ? 'selected' : '' }}>{{ $variationType }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-5 mb-2">
                                                            <label>Variation Value</label>
                                                            <select class="form-control" name="variants[{{ $index }}][variation_value_ids][]">
                                                                <option value="">Select Variation Value</option>
                                                                @foreach (($variationValues[$pairRow['type'] ?? ''] ?? []) as $value)
                                                                    <option value="{{ $value['id'] }}" {{ (string) ($pairRow['value_id'] ?? '') === (string) $value['id'] ? 'selected' : '' }}>{{ $value['value'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2 mb-2 d-flex align-items-end">
                                                            <button type="button" class="btn btn-outline-secondary w-100 remove-pair">Remove</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mb-3 add-pair">Add Variation Pair</button>

                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label>SKU Code</label>
                                                    <div class="input-group">
                                                        <input class="form-control variant-code" name="variants[{{ $index }}][sku_code]" value="{{ old("variants.$index.sku_code", $row['sku_code'] ?? '') }}">
                                                        <button type="button" class="btn btn-outline-primary generate-variant-code">Generate</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label>Barcode</label>
                                                    <div class="input-group">
                                                        <input class="form-control variant-barcode" name="variants[{{ $index }}][barcode]" value="{{ old("variants.$index.barcode", $row['barcode'] ?? '') }}">
                                                        <button type="button" class="btn btn-outline-primary generate-variant-barcode">Generate</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label>Status</label>
                                                    <select class="form-control variant-status" name="variants[{{ $index }}][status]">
                                                        <option value="active" {{ old("variants.$index.status", $row['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="inactive" {{ old("variants.$index.status", $row['status'] ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3"><label>Cost Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[{{ $index }}][cost_price]" value="{{ old("variants.$index.cost_price", $row['cost_price'] ?? '') }}"></div>
                                                <div class="col-md-3 mb-3"><label>MRP</label><input class="form-control variant-retail-price" type="number" min="0" step="0.01" name="variants[{{ $index }}][retail_price]" value="{{ old("variants.$index.retail_price", $row['retail_price'] ?? '') }}"></div>
                                                <div class="col-md-3 mb-3"><label>Wholesale Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[{{ $index }}][wholesale_price]" value="{{ old("variants.$index.wholesale_price", $row['wholesale_price'] ?? '') }}"></div>
                                                <div class="col-md-3 mb-3"><label>Minimum Selling Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[{{ $index }}][minimum_selling_price]" value="{{ old("variants.$index.minimum_selling_price", $row['minimum_selling_price'] ?? '') }}"></div>
                                                <div class="col-md-4 mb-3"><label>Online Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[{{ $index }}][online_price]" value="{{ old("variants.$index.online_price", $row['online_price'] ?? '') }}"></div>
                                                <div class="col-md-4 mb-3"><label>Weight</label><input class="form-control" type="number" min="0" step="0.01" name="variants[{{ $index }}][weight]" value="{{ old("variants.$index.weight", $row['weight'] ?? '') }}"></div>
                                                <div class="col-md-4 mb-3 medicine-pack-fields"><label>Tablets/Capsules Per Strip</label><input class="form-control units-per-strip" type="number" min="1" step="1" name="variants[{{ $index }}][units_per_strip]" value="{{ old("variants.$index.units_per_strip", $row['units_per_strip'] ?? 1) }}"></div>
                                                <div class="col-md-4 mb-3 medicine-pack-fields"><label>Per Medicine Price</label><input class="form-control medicine-unit-price" type="number" min="0" step="0.01" name="variants[{{ $index }}][medicine_unit_price]" value="{{ old("variants.$index.medicine_unit_price", $row['medicine_unit_price'] ?? '') }}"></div>
                                                <div class="col-md-4 mb-3"><label>Rating</label><input class="form-control" type="number" min="0" max="5" step="0.01" name="variants[{{ $index }}][rating]" value="{{ old("variants.$index.rating", $row['rating'] ?? '') }}"></div>
                                                <div class="col-md-8 mb-3"><label>Dosage Details</label><textarea class="form-control" rows="3" name="variants[{{ $index }}][dosage_details]">{{ old("variants.$index.dosage_details", $row['dosage_details'] ?? '') }}</textarea></div>
                                                <div class="col-md-4 mb-3"><label>Add Images</label><input class="form-control" type="file" name="variants[{{ $index }}][new_images][]" multiple accept="image/*"></div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="d-block">Track Stock</label>
                                                    <input type="hidden" name="variants[{{ $index }}][track_stock]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="variants[{{ $index }}][track_stock]" value="1" {{ old("variants.$index.track_stock", $row['track_stock'] ?? 1) ? 'checked' : '' }}>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="d-block">Track Batch</label>
                                                    <input type="hidden" name="variants[{{ $index }}][track_batch]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="variants[{{ $index }}][track_batch]" value="1" {{ old("variants.$index.track_batch", $row['track_batch'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="d-block">Track Expiry</label>
                                                    <input type="hidden" name="variants[{{ $index }}][track_expiry]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="variants[{{ $index }}][track_expiry]" value="1" {{ old("variants.$index.track_expiry", $row['track_expiry'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </div>

                                            @if ($images->isNotEmpty())
                                                <div class="row">
                                                    @foreach ($images as $image)
                                                        <div class="col-md-2 mb-3">
                                                            <img src="{{ url($image->image_path) }}" alt="" class="img-fluid rounded mb-2" style="height:100px;object-fit:cover;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="variants[{{ $index }}][delete_image_ids][]" value="{{ $image->id }}" id="variant-image-{{ $index }}-{{ $image->id }}" {{ in_array((string) $image->id, $deletedIds, true) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="variant-image-{{ $index }}-{{ $image->id }}">Remove</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-primary trigger-add-variant">Add Another Variant</button>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
    var variationValues = @json($variationValues);
    var variationTypes = @json($variationTypes);

    function trimFieldValue(element) {
        if (!element || typeof element.value === 'undefined' || element.value === null) {
            return '';
        }

        return String(element.value).replace(/^\s+|\s+$/g, '');
    }

    function ensureEmptyVariantState() {
        var $box = $('#variants');
        if (!$box.length || $box.find('.variant-card').length || $('#empty-variant-state').length) {
            return;
        }

        $box.prepend(
            '<div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" id="empty-variant-state">' +
                '<div>No variant SKU exists yet for this product. Add one now to start editing variant prices, SKU codes, and variation values.</div>' +
                '<button type="button" class="btn btn-primary trigger-add-variant">Add First Variant</button>' +
            '</div>'
        );
    }

    function generateCode(id, length) {
        var value = '';
        var i = 0;

        if (typeof length === 'undefined') {
            length = 12;
        }

        for (i = 0; i < length; i++) {
            value += Math.floor(Math.random() * 10);
        }

        var input = document.getElementById(id);
        if (input) {
            input.value = value;
        }
    }

    function initProductEditors(context) {
        var $context = context ? $(context) : $(document);

        if (!$.fn.summernote) {
            return;
        }

        $context.find('textarea.product-rich-editor').each(function () {
            var $textarea = $(this);
            if ($textarea.next('.note-editor').length) {
                return;
            }

            $textarea.summernote({
                tabsize: 2,
                height: 160,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ]
            });
        });
    }

    function syncProductEditors() {
        if (!$.fn.summernote) {
            return;
        }

        $('textarea.product-rich-editor').each(function () {
            var $textarea = $(this);
            if ($textarea.next('.note-editor').length) {
                $textarea.val($textarea.summernote('code'));
            }
        });
    }

    function selectedCategoryIsMedicine() {
        var text = $('#catPicker .cat-chip.active').map(function () { return $(this).text(); }).get().join(' ');
        return /(medicine|medicines|ঔষধ)/i.test(text);
    }

    function syncMedicinePackFields() {
        var isMedicine = selectedCategoryIsMedicine();
        $('.medicine-pack-fields').toggleClass('d-none', !isMedicine);
        if (!isMedicine) {
            $('.units-per-strip').val('1');
            $('.medicine-unit-price').val('');
        }
    }

    function typeOptions(selected) {
        var html = '<option value="">Select Variation Type</option>';
        var i = 0;

        if (typeof selected === 'undefined') {
            selected = '';
        }

        for (i = 0; i < variationTypes.length; i++) {
            html += '<option value="' + variationTypes[i] + '"' + (variationTypes[i] === selected ? ' selected' : '') + '>' + variationTypes[i] + '</option>';
        }

        return html;
    }

    function valueOptions(type, selected) {
        var html = '<option value="">Select Variation Value</option>';
        var items = variationValues[type] || [];
        var i = 0;

        if (typeof selected === 'undefined') {
            selected = '';
        }

        for (i = 0; i < items.length; i++) {
            html += '<option value="' + items[i].id + '"' + (String(items[i].id) === String(selected) ? ' selected' : '') + '>' + items[i].value + '</option>';
        }

        return html;
    }

    function pairHtml(index, type, value) {
        if (typeof type === 'undefined') {
            type = '';
        }

        if (typeof value === 'undefined') {
            value = '';
        }

        return '' +
            '<div class="row border rounded p-2 mb-2 variation-pair">' +
                '<div class="col-md-5 mb-2">' +
                    '<label>Variation Type</label>' +
                    '<select class="form-control variation-type" name="variants[' + index + '][variation_type_keys][]">' +
                        typeOptions(type) +
                    '</select>' +
                '</div>' +
                '<div class="col-md-5 mb-2">' +
                    '<label>Variation Value</label>' +
                    '<select class="form-control" name="variants[' + index + '][variation_value_ids][]">' +
                        valueOptions(type, value) +
                    '</select>' +
                '</div>' +
                '<div class="col-md-2 mb-2 d-flex align-items-end">' +
                    '<button type="button" class="btn btn-outline-secondary w-100 remove-pair">Remove</button>' +
                '</div>' +
            '</div>';
    }

    function variantHtml(index) {
        return '' +
            '<div class="card mb-3 variant-card" data-index="' + index + '">' +
                '<div class="card-body">' +
                    '<div class="variant-header mb-3">' +
                        '<div class="flex-grow-1">' +
                            '<strong class="variant-title">Variant</strong>' +
                            '<div class="variant-summary">No values selected yet</div>' +
                        '</div>' +
                        '<div class="variant-actions">' +
                            '<button type="button" class="btn btn-outline-primary btn-sm toggle-variant">Hide / Show Variant</button>' +
                            '<button type="button" class="btn btn-outline-danger btn-sm remove-variant">Remove Variant</button>' +
                            '<span class="variant-toggle-icon">&#9662;</span>' +
                        '</div>' +
                    '</div>' +
                    '<input type="hidden" name="variants[' + index + '][sku_id]" value="">' +
                    '<div class="variant-body">' +
                        '<div class="variation-pairs">' + pairHtml(index) + '</div>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm mb-3 add-pair">Add Variation Pair</button>' +
                        '<div class="row">' +
                            '<div class="col-md-4 mb-3"><label>SKU Code</label><div class="input-group"><input class="form-control variant-code" id="variant-code-' + index + '" name="variants[' + index + '][sku_code]"><button type="button" class="btn btn-outline-primary generate-variant-code">Generate</button></div></div>' +
                            '<div class="col-md-4 mb-3"><label>Barcode</label><div class="input-group"><input class="form-control variant-barcode" id="variant-barcode-' + index + '" name="variants[' + index + '][barcode]"><button type="button" class="btn btn-outline-primary generate-variant-barcode">Generate</button></div></div>' +
                            '<div class="col-md-4 mb-3"><label>Status</label><select class="form-control variant-status" name="variants[' + index + '][status]"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>' +
                            '<div class="col-md-3 mb-3"><label>Cost Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[' + index + '][cost_price]"></div>' +
                            '<div class="col-md-3 mb-3"><label>MRP</label><input class="form-control variant-retail-price" type="number" min="0" step="0.01" name="variants[' + index + '][retail_price]"></div>' +
                            '<div class="col-md-3 mb-3"><label>Wholesale Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[' + index + '][wholesale_price]"></div>' +
                            '<div class="col-md-3 mb-3"><label>Minimum Selling Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[' + index + '][minimum_selling_price]"></div>' +
                            '<div class="col-md-4 mb-3"><label>Online Price</label><input class="form-control" type="number" min="0" step="0.01" name="variants[' + index + '][online_price]"></div>' +
                            '<div class="col-md-4 mb-3"><label>Weight</label><input class="form-control" type="number" min="0" step="0.01" name="variants[' + index + '][weight]"></div>' +
                            '<div class="col-md-4 mb-3 medicine-pack-fields"><label>Tablets/Capsules Per Strip</label><input class="form-control units-per-strip" type="number" min="1" step="1" name="variants[' + index + '][units_per_strip]" value="1"></div>' +
                            '<div class="col-md-4 mb-3 medicine-pack-fields"><label>Per Medicine Price</label><input class="form-control medicine-unit-price" type="number" min="0" step="0.01" name="variants[' + index + '][medicine_unit_price]"></div>' +
                            '<div class="col-md-4 mb-3"><label>Rating</label><input class="form-control" type="number" min="0" max="5" step="0.01" name="variants[' + index + '][rating]"></div>' +
                            '<div class="col-md-8 mb-3"><label>Dosage Details</label><textarea class="form-control" rows="3" name="variants[' + index + '][dosage_details]"></textarea></div>' +
                            '<div class="col-md-4 mb-3"><label>Add Images</label><input class="form-control" type="file" name="variants[' + index + '][new_images][]" multiple accept="image/*"></div>' +
                            '<div class="col-md-3 mb-3"><label class="d-block">Track Stock</label><input type="hidden" name="variants[' + index + '][track_stock]" value="0"><input class="form-check-input" type="checkbox" name="variants[' + index + '][track_stock]" value="1" checked></div>' +
                            '<div class="col-md-3 mb-3"><label class="d-block">Track Batch</label><input type="hidden" name="variants[' + index + '][track_batch]" value="0"><input class="form-check-input" type="checkbox" name="variants[' + index + '][track_batch]" value="1"></div>' +
                            '<div class="col-md-3 mb-3"><label class="d-block">Track Expiry</label><input type="hidden" name="variants[' + index + '][track_expiry]" value="0"><input class="form-check-input" type="checkbox" name="variants[' + index + '][track_expiry]" value="1"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function updateVariantSummary(card) {
        var $card = $(card);
        var values = [];
        var code = trimFieldValue($card.find('.variant-code').get(0));
        var retail = trimFieldValue($card.find('.variant-retail-price').get(0));
        var status = trimFieldValue($card.find('.variant-status').get(0));
        var parts = [];

        $card.find('select[name$="[variation_value_ids][]"] option:selected').each(function () {
            if (this.value) {
                values.push($.trim($(this).text()));
            }
        });

        if (values.length) {
            parts.push(values.join(', '));
        }
        if (code) {
            parts.push('SKU: ' + code);
        }
        if (retail) {
            parts.push('Retail: ' + retail);
        }
        if (status) {
            parts.push('Status: ' + status);
        }

        $card.find('.variant-summary').text(parts.length ? parts.join(' | ') : 'No values selected yet');
    }

    function toggleVariantCard(card, openState) {
        var $card = $(card);
        var $body = $card.find('.variant-body').first();
        var shouldOpen = typeof openState === 'undefined' || openState === null
            ? $body.css('display') === 'none'
            : openState;

        $body.toggle(shouldOpen);
        $card.toggleClass('is-collapsed', !shouldOpen);
    }

    function renumberVariantTitles() {
        $('#variants .variant-card').each(function (index) {
            $(this).find('.variant-title').first().text('Variant ' + (index + 1));
        });
    }

    function isSingleSkuType(type) {
        return type === 'Single' || type === 'Combo' || type === 'Service';
    }

    function syncSingleSkuControls(type) {
        var isService = type === 'Service';
        var names = ['track_stock', 'track_batch', 'track_expiry'];
        var i = 0;

        $('#serviceTypeNote').toggleClass('d-none', !isService);

        for (i = 0; i < names.length; i++) {
            var $checkbox = $('input[name="' + names[i] + '"][type="checkbox"]');
            if (!$checkbox.length) {
                continue;
            }

            if (isService) {
                $checkbox.prop('checked', false);
            }

            $checkbox.prop('disabled', isService);
        }
    }

    function changeProductType() {
        var type = $('#type').val();
        $('#singleProductDiv').toggle(isSingleSkuType(type));
        $('#variationProductDiv').toggle(type === 'Variation');
        syncSingleSkuControls(type);
    }

    $(document).on('change', '#type', function () {
        changeProductType();
    });

    $(document).on('change', '.variation-type', function () {
        var $wrap = $(this).closest('.variation-pair');
        $wrap.find('select[name$="[variation_value_ids][]"]').html(valueOptions($(this).val())).trigger('change');
        var $card = $(this).closest('.variant-card');
        if ($card.length) {
            updateVariantSummary($card.get(0));
        }
    });

    $(document).on('change', '#variants .variant-card select', function () {
        var $card = $(this).closest('.variant-card');
        if ($card.length) {
            updateVariantSummary($card.get(0));
        }
    });

    $(document).on('input', '.variant-code, .variant-retail-price, .variant-barcode', function () {
        var $card = $(this).closest('.variant-card');
        if ($card.length) {
            updateVariantSummary($card.get(0));
        }
    });

    $(document).on('click', '#add-variant, .trigger-add-variant', function () {
        var $box = $('#variants');
        var index = Number($box.attr('data-next-index') || 0);
        $('#empty-variant-state').remove();
        $box.append(variantHtml(index));
        $box.attr('data-next-index', index + 1);
        var $card = $box.find('.variant-card').last();
        syncMedicinePackFields();
        if (typeof initSelect2 === 'function') initSelect2($card[0]);
        updateVariantSummary($card.get(0));
        toggleVariantCard($card.get(0), true);
        renumberVariantTitles();
    });

    $(document).on('click', '#expand-all-variants', function () {
        $('#variants .variant-card').each(function () {
            toggleVariantCard(this, true);
        });
    });

    $(document).on('click', '#collapse-all-variants', function () {
        $('#variants .variant-card').each(function () {
            toggleVariantCard(this, false);
        });
    });

    $(document).on('click', '.toggle-variant', function () {
        toggleVariantCard($(this).closest('.variant-card').get(0));
    });

    $(document).on('click', '.add-pair', function () {
        var $card = $(this).closest('.variant-card');
        var index = $card.attr('data-index');
        $card.find('.variation-pairs').append(pairHtml(index));
        if (typeof initSelect2 === 'function') initSelect2($card.find('.variation-pairs').last()[0]);
        updateVariantSummary($card.get(0));
    });

    $(document).on('click', '.remove-pair', function () {
        var $card = $(this).closest('.variant-card');
        var $group = $(this).closest('.variation-pairs');
        if ($group.find('.variation-pair').length > 1) {
            $(this).closest('.variation-pair').remove();
            updateVariantSummary($card.get(0));
        }
    });

    $(document).on('click', '.remove-variant', function () {
        var $card = $(this).closest('.variant-card');
        var skuId = $card.find('input[name$="[sku_id]"]').val();
        if (skuId) {
            $('#removed-variant-ids').append('<input type="hidden" name="remove_variant_ids[]" value="' + skuId + '">');
        }

        $card.remove();
        renumberVariantTitles();
        ensureEmptyVariantState();
    });

    $(document).on('click', '.generate-variant-code', function () {
        var input = $(this).closest('.input-group').find('.variant-code').get(0);
        if (!input.id) {
            input.id = 'variant-code-' + Math.random().toString(36).slice(2);
        }
        generateCode(input.id);
        updateVariantSummary($(this).closest('.variant-card').get(0));
    });

    $(document).on('click', '.generate-variant-barcode', function () {
        var input = $(this).closest('.input-group').find('.variant-barcode').get(0);
        if (!input.id) {
            input.id = 'variant-barcode-' + Math.random().toString(36).slice(2);
        }
        generateCode(input.id);
    });

    $('#variants .variant-card').each(function () {
        updateVariantSummary(this);
    });
    renumberVariantTitles();
    changeProductType();
    initProductEditors();
    syncMedicinePackFields();
    $(document).on('click', '#catPicker .cat-chip', syncMedicinePackFields);
    $('#productEditForm').on('submit', syncProductEditors);
</script>
@endsection
