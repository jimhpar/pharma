@extends('layouts.main')

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
        --pf-table-stripe: rgba(15, 23, 42, 0.02);
    }

    html[data-bs-theme="dark"] .product-form-page {
        --pf-surface: #111827;
        --pf-surface-alt: #0f172a;
        --pf-border: rgba(148, 163, 184, 0.2);
        --pf-text: #e5edf8;
        --pf-muted: #9fb0c5;
        --pf-input-bg: #0b1220;
        --pf-input-border: rgba(148, 163, 184, 0.24);
        --pf-table-stripe: rgba(148, 163, 184, 0.04);
    }

    .product-form-page,
    .product-form-page label,
    .product-form-page .card-title,
    .product-form-page .section-title,
    .product-form-page h3,
    .product-form-page h4,
    .product-form-page h5 {
        color: var(--pf-text);
    }

    .product-form-page .text-muted,
    .product-form-page .breadcrumb-item,
    .product-form-page .small {
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

    .variation-row {
        border: 1px dashed var(--pf-border);
        border-radius: 0.75rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
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

    .product-form-page .input-group-text,
    .product-form-page .table,
    .product-form-page .table th,
    .product-form-page .table td {
        color: var(--pf-text);
    }

    .product-form-page .table-striped > tbody > tr:nth-of-type(odd) > * {
        background: var(--pf-table-stripe);
    }

    .product-form-page .table > :not(caption) > * > * {
        background: transparent;
        border-bottom-color: var(--pf-border);
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
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Product Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Product Create</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Product Form</h4>
            </div>
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

                <form id="productForm" method="post" action="{{ route('product.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="section-card">
                        <div class="section-title">General Details</div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input class="form-control" name="name" value="{{ old('name') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Slug <span class="text-danger">*</span></label>
                                <input class="form-control" name="slug" value="{{ old('slug') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" id="type">
                                    <option value="">Select Type</option>
                                    <option value="Single" {{ old('type') === 'Single' ? 'selected' : '' }}>Standard</option>
                                    <option value="Variation" {{ old('type') === 'Variation' ? 'selected' : '' }}>Variant</option>
                                    <option value="Combo" {{ old('type') === 'Combo' ? 'selected' : '' }}>Combo</option>
                                    <option value="Service" {{ old('type') === 'Service' ? 'selected' : '' }}>Service</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label>Category <span class="text-danger">*</span></label>
                                @include('product.partials.category-picker', [
                                    'pickerId' => 'catPicker',
                                    'options'  => $category,
                                    'selected' => (array) old('categories', []),
                                ])
                                @error('categories')
                                    <div class="text-danger small mt-1"><strong>{{ $message }}</strong></div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Brand</label>
                                <select class="form-control no-select2" id="brandSelectCreate" name="brand">
                                    <option value="">Select Brand</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Unit</label>
                                <select class="form-control" name="unit_id">
                                    <option value="">Select Unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" {{ (string) old('unit_id') === (string) $unit->id ? 'selected' : '' }}>
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
                                        <option value="{{ $taxRule->id }}" {{ (string) old('tax_rule_id') === (string) $taxRule->id ? 'selected' : '' }}>
                                            {{ $taxRule->name }} ({{ $taxRule->rate_percent }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Status <span class="text-danger">*</span></label>
                                <select class="form-control" name="status">
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Thumbnail Image <span class="text-danger">*</span></label>
                                <input class="form-control" type="file" name="thumbnail_image" accept="image/*">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>SEO Title</label>
                                <input class="form-control" name="seo_title" value="{{ old('seo_title') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Generic Name</label>
                                <input class="form-control" type="text" name="generic_name" value="{{ old('generic_name') }}" placeholder="e.g. Paracetamol">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Short Description</label>
                                <textarea class="form-control product-rich-editor" rows="4" name="short_description">{{ old('short_description') }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Long Description</label>
                                <textarea class="form-control product-rich-editor" rows="4" name="long_description">{{ old('long_description') }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>SEO Description</label>
                                <textarea class="form-control" rows="3" name="seo_description">{{ old('seo_description') }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Product Warnings</label>
                                <textarea class="form-control" rows="3" name="warnings">{{ old('warnings') }}</textarea>
                            </div>
                            <div class="col-md-3 col-xl mb-3">
                                <label class="d-block">Featured</label>
                                <input type="hidden" name="is_featured" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_featured">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 col-xl mb-3">
                                <label class="d-block">Flash Deals</label>
                                <input type="hidden" name="is_flash_deals" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_flash_deals" name="is_flash_deals" value="1" {{ old('is_flash_deals') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_flash_deals">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 col-xl mb-3">
                                <label class="d-block">Popular</label>
                                <input type="hidden" name="is_popular" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular" value="1" {{ old('is_popular') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_popular">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 col-xl mb-3">
                                <label class="d-block">Online Enabled</label>
                                <input type="hidden" name="is_online_enabled" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_online_enabled" name="is_online_enabled" value="1" {{ old('is_online_enabled', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_online_enabled">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 col-xl mb-3">
                                <label class="d-block">POS Enabled</label>
                                <input type="hidden" name="is_pos_enabled" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="is_pos_enabled" name="is_pos_enabled" value="1" {{ old('is_pos_enabled', 1) ? 'checked' : '' }}>
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

                    <div id="singleProductDiv" class="section-card">
                        <div class="section-title">SKU Details</div>
                        <div class="alert alert-info d-none" id="serviceTypeNote">
                            Service products are treated as non-stock items. Stock, batch, and expiry tracking will be turned off automatically.
                        </div>
                        <div class="row">
                            {{-- ── 1. Identification ── --}}
                            <div class="col-md-4 mb-3">
                                <label>SKU Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input class="form-control" id="single_sku_code" name="sku_code" value="{{ old('sku_code') }}">
                                    <button class="btn btn-outline-primary" type="button" onclick="generateSkuCode('single_sku_code')">Generate</button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Barcode</label>
                                <div class="input-group">
                                    <input class="form-control" id="single_barcode" name="barcode" value="{{ old('barcode') }}">
                                    <button class="btn btn-outline-primary" type="button" onclick="generateCode('single_barcode')">Generate</button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>SKU Status <span class="text-danger">*</span></label>
                                <select class="form-control" name="sku_status">
                                    <option value="active" {{ old('sku_status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('sku_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            {{-- ── 2. Core Pricing ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Pricing</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Cost Price <span class="text-muted small">(TP)</span></label>
                                <input class="form-control" type="number" min="0" step="0.01" name="cost_price" value="{{ old('cost_price') }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>MRP <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" min="0" step="0.01" name="retail_price" value="{{ old('retail_price') }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Sale Price <span class="text-muted small">(POS default)</span></label>
                                <input class="form-control" type="number" min="0" step="0.01" name="sale_price" value="{{ old('sale_price') }}" placeholder="= MRP if blank">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Min Selling Price</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="minimum_selling_price" value="{{ old('minimum_selling_price') }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Online Price</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="online_price" value="{{ old('online_price') }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Wholesale Price</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="wholesale_price" value="{{ old('wholesale_price') }}" placeholder="0.00">
                            </div>

                            {{-- ── 3. Discount ── --}}
                            <div class="col-md-6 mb-3">
                                <label>Default Discount Type</label>
                                <select class="form-control no-select2" name="default_discount_type">
                                    <option value="">No Discount</option>
                                    <option value="percent" {{ old('default_discount_type') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="amount"  {{ old('default_discount_type') === 'amount'  ? 'selected' : '' }}>Fixed Amount (৳)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Discount Value</label>
                                <div class="input-group">
                                    <input class="form-control" type="number" step="0.01" min="0" name="default_discount_value"
                                        value="{{ old('default_discount_value', 0) }}" placeholder="0">
                                    <span class="input-group-text">% / ৳</span>
                                </div>
                                <small class="text-muted">Final = Sale Price − Discount</small>
                            </div>

                            {{-- ── 4. Pharma Info ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Pharma Info</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Dosage Form</label>
                                <input class="form-control" type="text" name="dosage_form"
                                    value="{{ old('dosage_form') }}" placeholder="e.g. Tablet, Capsule, Injection, Syrup">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Strength</label>
                                <input class="form-control" type="text" name="strength"
                                    value="{{ old('strength') }}" placeholder="e.g. 500mg, 45.5mg/2ml">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Coating Type <span class="text-muted small">(optional)</span></label>
                                <input class="form-control" type="text" name="coating_type"
                                    value="{{ old('coating_type') }}" placeholder="e.g. Film-coated">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Dosage Details</label>
                                <textarea class="form-control" rows="2" name="dosage_details">{{ old('dosage_details') }}</textarea>
                            </div>
                            <div class="col-md-3 mb-3 medicine-pack-fields">
                                <label>Tablets / Capsules Per Strip</label>
                                <input class="form-control units-per-strip" type="number" min="1" step="1" name="units_per_strip" value="{{ old('units_per_strip', 1) }}">
                            </div>
                            <div class="col-md-3 mb-3 medicine-pack-fields">
                                <label>Per Medicine Price</label>
                                <input class="form-control medicine-unit-price" type="number" min="0" step="0.01" name="medicine_unit_price" value="{{ old('medicine_unit_price') }}" placeholder="0.00">
                            </div>

                            {{-- ── 5. Other ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Other</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Weight (g)</label>
                                <input class="form-control" type="number" min="0" step="0.01" name="weight" value="{{ old('weight') }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Rating</label>
                                <input class="form-control" type="number" min="0" max="5" step="0.1" name="rating" value="{{ old('rating') }}" placeholder="0 - 5">
                            </div>

                            {{-- ── 6. Tracking ── --}}
                            <div class="col-12 mb-1">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.07em">Stock Tracking</small>
                                <hr class="mt-1 mb-2">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Track Stock</label>
                                <input type="hidden" name="track_stock" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="track_stock" name="track_stock" value="1" {{ old('track_stock', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="track_stock">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Track Batch</label>
                                <input type="hidden" name="track_batch" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="track_batch" name="track_batch" value="1" {{ old('track_batch') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="track_batch">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Track Expiry</label>
                                <input type="hidden" name="track_expiry" value="0">
                                <div class="checkbox-stack">
                                    <input class="form-check-input" type="checkbox" id="track_expiry" name="track_expiry" value="1" {{ old('track_expiry') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="track_expiry">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Product Images</label>
                                <input class="form-control" type="file" name="single_product_images[]" multiple accept="image/*">
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Create Product</button>
                        </div>
                    </div>

                    <div id="variationProductDiv">
                        <div class="section-card">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <div class="section-title mb-1">Variant Defaults</div>
                                    <div class="text-muted small" id="variationCountText">Add at least one variation, then submit the product.</div>
                                </div>
                                <button type="submit" class="btn btn-success" id="variationFinalSubmitBtn" disabled>Submit Full Product</button>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3 mb-3">
                                    <label>Variant SKU Status <span class="text-danger">*</span></label>
                                    <select class="form-control" name="variation_sku_status">
                                        <option value="active" {{ old('variation_sku_status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('variation_sku_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="d-block">Track Stock</label>
                                    <input type="hidden" name="variation_track_stock" value="0">
                                    <div class="checkbox-stack">
                                        <input class="form-check-input" type="checkbox" id="variation_track_stock" name="variation_track_stock" value="1" {{ old('variation_track_stock', 1) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="variation_track_stock">Enabled</label>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="d-block">Track Batch</label>
                                    <input type="hidden" name="variation_track_batch" value="0">
                                    <div class="checkbox-stack">
                                        <input class="form-check-input" type="checkbox" id="variation_track_batch" name="variation_track_batch" value="1" {{ old('variation_track_batch') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="variation_track_batch">Enabled</label>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="d-block">Track Expiry</label>
                                    <input type="hidden" name="variation_track_expiry" value="0">
                                    <div class="checkbox-stack">
                                        <input class="form-check-input" type="checkbox" id="variation_track_expiry" name="variation_track_expiry" value="1" {{ old('variation_track_expiry') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="variation_track_expiry">Enabled</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="variationBuilderArea" style="display:none;">
                    <div class="section-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0">Add Variant To Temp List</div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addVariationRow">Add Variation</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="removeVariationRow">Remove Last</button>
                            </div>
                        </div>

                        <form id="variationForm" method="post" action="{{ route('product.tempStore') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="temp_id" id="variation_temp_id" value="">
                            <div class="alert alert-warning d-none" id="variationEditState">
                                Editing a saved temp variant. Uploading new images will replace the images currently saved on that temp row.
                            </div>
                            <div id="variationRows">
                                <div class="variation-row">
                                    <div class="row">
                                        <div class="col-md-5 mb-3">
                                            <label>Variation Type <span class="text-danger">*</span></label>
                                            <select class="form-control variation-type" name="variation_type">
                                                <option value="">Select Variation Type</option>
                                                @foreach ($variation->unique('type') as $variationType)
                                                    <option value="{{ $variationType->id }}">{{ $variationType->type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <label>Variation Value <span class="text-danger">*</span></label>
                                            <select class="form-control variation-value" name="variation_value">
                                                <option value="">Select Variation Value</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-secondary w-100 remove-row">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>SKU Code <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input class="form-control" id="variation_sku_code" name="variation_sku_code" value="{{ old('variation_sku_code') }}">
                                        <button class="btn btn-outline-primary" type="button" onclick="generateSkuCode('variation_sku_code')">Generate</button>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Barcode</label>
                                    <div class="input-group">
                                        <input class="form-control" id="variation_barcode" name="variation_barcode" value="{{ old('variation_barcode') }}">
                                        <button class="btn btn-outline-primary" type="button" onclick="generateCode('variation_barcode')">Generate</button>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Weight</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="weight" value="{{ old('weight') }}">
                                </div>
                                <div class="col-md-4 mb-3 medicine-pack-fields">
                                    <label>Tablets/Capsules Per Strip</label>
                                    <input class="form-control units-per-strip" type="number" min="1" step="1" name="variation_units_per_strip" value="{{ old('variation_units_per_strip', 1) }}">
                                </div>
                                <div class="col-md-4 mb-3 medicine-pack-fields">
                                    <label>Per Medicine Price</label>
                                    <input class="form-control medicine-unit-price" type="number" min="0" step="0.01" name="variation_medicine_unit_price" value="{{ old('variation_medicine_unit_price') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Rating</label>
                                    <input class="form-control" type="number" min="0" max="5" step="0.01" name="rating" value="{{ old('rating') }}">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label>Dosage Details</label>
                                    <textarea class="form-control" rows="3" name="dosage_details">{{ old('dosage_details') }}</textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Cost Price</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="variation_cost_price" value="{{ old('variation_cost_price') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>MRP <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="variation_retail_price" value="{{ old('variation_retail_price') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Sale Price</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="variation_sale_price" value="{{ old('variation_sale_price') }}" placeholder="= MRP if blank">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Wholesale Price</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="variation_wholesale_price" value="{{ old('variation_wholesale_price') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Minimum Selling Price</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="minimum_selling_price" value="{{ old('minimum_selling_price') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Online Price</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="online_price" value="{{ old('online_price') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Images</label>
                                    <input class="form-control" type="file" name="variation_product_images[]" multiple accept="image/*">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Specifications</label>
                                    <textarea class="form-control product-rich-editor" rows="4" name="variation_specifications">{{ old('variation_specifications') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Additional Description</label>
                                    <textarea class="form-control product-rich-editor" rows="4" name="variation_additional_description">{{ old('variation_additional_description') }}</textarea>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-outline-secondary d-none" id="cancelVariationEdit">Cancel Edit</button>
                                <button type="submit" class="btn btn-primary" id="variationSubmitBtn">Save Variant To Temp</button>
                            </div>
                        </form>
                    </div>

                    <div class="section-card">
                        <div class="section-title">Saved Variant Temp List</div>
                        <div class="table-responsive">
                            <table id="variationTempTable" class="table table-striped" width="100%"></table>
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
$(function () {
    $('#brandSelectCreate').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search brand…',
        allowClear: true,
        ajax: {
            url: @json(route('product.brandSearch')),
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
            cache: true,
        },
        minimumInputLength: 0,
    });
});
</script>
<script>
    function generateCode(id, length) {
        var value = '';
        var chars = '0123456789';
        var i = 0;

        if (typeof length === 'undefined') {
            length = 12;
        }

        for (i = 0; i < length; i++) {
            value += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        var input = document.getElementById(id);
        if (input) {
            input.value = value;
        }
    }

    function getSelectedCategoryIds() {
        return $('#catPicker-hidden option:selected').map(function () {
            return this.value;
        }).get().filter(function (value) {
            return value !== '';
        });
    }

    function collectSkuCodes(exceptId) {
        return $('input[name="sku_code"], input[name="variation_sku_code"]').map(function () {
            if (this.id === exceptId) {
                return null;
            }

            return $.trim($(this).val());
        }).get().filter(function (value) {
            return value !== '';
        });
    }

    function showSkuGeneratorError(message) {
        if (window.toastr) {
            toastr.error(message);
            return;
        }

        alert(message);
    }

    function generateSkuCode(id) {
        var categoryIds = getSelectedCategoryIds();
        var input = document.getElementById(id);

        if (categoryIds.length !== 1) {
            showSkuGeneratorError('Please select exactly one category before generating a SKU code.');
            return;
        }

        $.ajax({
            type: 'GET',
            url: @json(route('product.generateSkuCode')),
            data: {
                category_id: categoryIds[0],
                existing_codes: collectSkuCodes(id)
            },
            success: function (response) {
                if (input && response && response.sku_code) {
                    input.value = response.sku_code;
                }
            },
            error: function () {
                showSkuGeneratorError('Could not generate SKU code. Please try again.');
            }
        });
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

    function buildVariationRow() {
        return '' +
            '<div class="variation-row">' +
                '<div class="row">' +
                    '<div class="col-md-5 mb-3">' +
                        '<label>Variation Type <span class="text-danger">*</span></label>' +
                        '<select class="form-control variation-type" name="variation_type">' +
                            '<option value="">Select Variation Type</option>' +
                            '@foreach ($variation->unique("type") as $variationType)' +
                                '<option value="{{ $variationType->id }}">{{ $variationType->type }}</option>' +
                            '@endforeach' +
                        '</select>' +
                    '</div>' +
                    '<div class="col-md-5 mb-3">' +
                        '<label>Variation Value <span class="text-danger">*</span></label>' +
                        '<select class="form-control variation-value" name="variation_value">' +
                            '<option value="">Select Variation Value</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="col-md-2 mb-3 d-flex align-items-end">' +
                        '<button type="button" class="btn btn-outline-secondary w-100 remove-row">Remove</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function setVariationSubmitState(variationCount) {
        var total = Number(variationCount) || 0;
        var canSubmit = total > 0;
        var message = 'Add at least one variation, then submit the product.';

        if (canSubmit) {
            message = total + ' variant' + (total === 1 ? '' : 's') + ' saved in temp. You can submit the product now.';
        }

        $('#variationFinalSubmitBtn').prop('disabled', !canSubmit);
        $('#variationCountText').text(message);
    }

    function resetVariationForm() {
        syncProductEditors();
        $('#variationForm')[0].reset();
        $('#variation_temp_id').val('');
        $('#variationRows').html(buildVariationRow());
        if (typeof initSelect2 === 'function') initSelect2($('#variationRows')[0]);
        $('input[name="variation_units_per_strip"]').val('1');
        $('input[name="variation_medicine_unit_price"]').val('');
        $('input[name="rating"]').val('');
        $('textarea[name="dosage_details"]').val('');
        $('textarea[name="variation_specifications"]').summernote('code', '');
        $('textarea[name="variation_additional_description"]').summernote('code', '');
        $('#variationEditState').addClass('d-none');
        $('#cancelVariationEdit').addClass('d-none');
        $('#variationSubmitBtn').text('Save Variant To Temp');
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
        var table = null;

        $('#singleProductDiv').hide();
        $('#variationProductDiv').hide();
        $('#variationBuilderArea').hide();

        if (isSingleSkuType(type)) {
            $('#singleProductDiv').show();
            syncSingleSkuControls(type);
        }

        if (type === 'Variation') {
            $('#variationProductDiv').show();
            $('#variationBuilderArea').show();
            table = $.fn.DataTable.isDataTable('#variationTempTable') ? $('#variationTempTable').DataTable() : null;
            setVariationSubmitState(table ? table.rows().count() : 0);
            syncSingleSkuControls(type);
        }

        if (!isSingleSkuType(type) && type !== 'Variation') {
            syncSingleSkuControls(type);
        }
    }

    function loadVariationValues(selectElement, selectedValue) {
        var variationTypeId = $(selectElement).val();
        var valueSelect = $(selectElement).closest('.variation-row').find('.variation-value');

        if (typeof selectedValue === 'undefined') {
            selectedValue = '';
        }

        valueSelect.html('<option value="">Select Variation Value</option>').trigger('change');
        if (!variationTypeId) {
            return;
        }

        $.ajax({
            type: 'GET',
            url: "{{ route('product.getVariationValues') }}",
            data: { type_id: variationTypeId },
            success: function (response) {
                var i = 0;
                for (i = 0; i < response.length; i++) {
                    valueSelect.append('<option value="' + response[i].id + '">' + response[i].value + '</option>');
                }

                if (selectedValue) {
                    valueSelect.val(String(selectedValue));
                }
                valueSelect.trigger('change');
            },
            error: function () {
                toastr.error('Failed to load variation values.');
            }
        });
    }

    function tempEdit(button) {
        var id = $(button).data('panel-id');

        $.ajax({
            type: 'GET',
            url: "{{ route('product.variationEdit') }}",
            data: { temp_id: id },
            success: function (response) {
                var variation = response.variation || {};
                var selectedVariations = response.selected_variations || [];
                var form = $('#variationForm');
                var i = 0;

                resetVariationForm();
                $('#variation_temp_id').val(variation.id || '');
                form.find('#variation_sku_code').val(variation.variation_sku_code || '');
                form.find('#variation_barcode').val(variation.variation_barcode || '');
                form.find('input[name="weight"]').val(variation.weight || '');
                form.find('input[name="variation_units_per_strip"]').val(variation.variation_units_per_strip || 1);
                form.find('input[name="variation_medicine_unit_price"]').val(variation.variation_medicine_unit_price || '');
                form.find('input[name="rating"]').val(variation.rating || '');
                form.find('textarea[name="dosage_details"]').val(variation.dosage_details || '');
                form.find('input[name="variation_cost_price"]').val(variation.variation_cost_price || '');
                form.find('input[name="variation_retail_price"]').val(variation.variation_retail_price || '');
                form.find('input[name="variation_wholesale_price"]').val(variation.variation_wholesale_price || '');
                form.find('input[name="minimum_selling_price"]').val(variation.minimum_selling_price || '');
                form.find('input[name="online_price"]').val(variation.online_price || '');
                form.find('textarea[name="variation_specifications"]').val(variation.variation_specifications || '');
                form.find('textarea[name="variation_additional_description"]').val(variation.variation_additional_description || '');
                form.find('textarea[name="variation_specifications"]').summernote('code', variation.variation_specifications || '');
                form.find('textarea[name="variation_additional_description"]').summernote('code', variation.variation_additional_description || '');

                if (selectedVariations.length > 0) {
                    $('#variationRows').empty();

                    for (i = 0; i < selectedVariations.length; i++) {
                        var item = selectedVariations[i];
                        $('#variationRows').append(buildVariationRow());
                        var row = $('#variationRows .variation-row:last');
                        var typeSelect = row.find('.variation-type');
                        var matchedTypeOption = typeSelect.find('option').filter(function () {
                            return $.trim($(this).text()) === $.trim(String(item.type || ''));
                        }).first();

                        if (matchedTypeOption.length) {
                            typeSelect.val(matchedTypeOption.val());
                            loadVariationValues(typeSelect, item.id);
                        }
                    }
                }

                $('#variationEditState').removeClass('d-none');
                $('#cancelVariationEdit').removeClass('d-none');
                $('#variationSubmitBtn').text('Update Variant Temp');
                $('html, body').animate({ scrollTop: $('#variationForm').offset().top - 20 }, 200);
            },
            error: function () {
                toastr.error('Could not load the selected temp variant.');
            }
        });
    }

    function tempDelete(button) {
        var id = $(button).data('panel-id');
        if (!confirm('Delete this temp variant?')) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: "{{ route('product.tempvariationDelete') }}",
            data: {
                _token: "{{ csrf_token() }}",
                temp_id: id
            },
            success: function () {
                if ($('#variation_temp_id').val() === String(id)) {
                    resetVariationForm();
                }
                toastr.success('Variant removed from temp list.');
                $('#variationTempTable').DataTable().ajax.reload(null, false);
            }
        });
    }

    $(document).ready(function () {
        changeProductType();
        initProductEditors();

        $('#type').on('change', changeProductType);
        $(document).on('click', '#catPicker .cat-chip', syncMedicinePackFields);
        $('#productForm').on('submit', syncProductEditors);
        syncMedicinePackFields();

        $(document).on('change', '.variation-type', function () {
            loadVariationValues(this);
        });

        $('#cancelVariationEdit').on('click', function () {
            resetVariationForm();
        });

        $('#addVariationRow').on('click', function () {
            $('#variationRows').append(buildVariationRow());
            if (typeof initSelect2 === 'function') initSelect2($('#variationRows .variation-row:last')[0]);
        });

        $('#removeVariationRow').on('click', function () {
            var rows = $('#variationRows .variation-row');
            if (rows.length > 1) {
                rows.last().remove();
            }
        });

        $(document).on('click', '.remove-row', function () {
            var rows = $('#variationRows .variation-row');
            if (rows.length > 1) {
                $(this).closest('.variation-row').remove();
            }
        });

        $('#variationForm').on('submit', function (e) {
            var variationIds = [];
            var formData = null;
            var errorGroups = null;
            var errorKey = null;
            var groupIndex = 0;
            var messageIndex = 0;

            e.preventDefault();
            syncProductEditors();

            $('#variationRows .variation-value').each(function () {
                var value = $(this).val();
                if (value) {
                    variationIds.push(value);
                }
            });

            if (variationIds.length === 0) {
                toastr.error('Please select at least one variation value.');
                return;
            }

            formData = new FormData(this);
            formData.delete('variation_value');
            for (groupIndex = 0; groupIndex < variationIds.length; groupIndex++) {
                formData.append('variation_value[]', variationIds[groupIndex]);
            }

            $.ajax({
                type: 'POST',
                url: "{{ route('product.tempStore') }}",
                cache: false,
                processData: false,
                contentType: false,
                data: formData,
                success: function (response) {
                    toastr.success(response.success || 'Variant saved to temp list.');
                    resetVariationForm();
                    $('#variationTempTable').DataTable().ajax.reload(null, false);
                },
                error: function (error) {
                    if (error.status === 422) {
                        errorGroups = error.responseJSON && error.responseJSON.errors ? error.responseJSON.errors : {};
                        for (errorKey in errorGroups) {
                            if (Object.prototype.hasOwnProperty.call(errorGroups, errorKey)) {
                                for (messageIndex = 0; messageIndex < errorGroups[errorKey].length; messageIndex++) {
                                    toastr.error(errorGroups[errorKey][messageIndex] || 'Please fix the highlighted fields.');
                                    return;
                                }
                            }
                        }

                        toastr.error('Please fix the highlighted fields.');
                        return;
                    }

                    toastr.error('Could not save the variant temp row.');
                }
            });
        });

        $('#variationTempTable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: "{{ route('product.tempList') }}",
                type: 'POST',
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                }
            },
            fnDrawCallback: function () {
                setVariationSubmitState(this.api().rows().count());
            },
            columns: [
                {
                    title: 'Variation Values',
                    data: 'variation_values',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        if (!data) {
                            return '<span class="badge bg-secondary">No values</span>';
                        }

                        return '<span class="badge bg-info">' + data + '</span>';
                    }
                },
                { title: 'SKU Code', data: 'variation_sku_code', name: 'variation_sku_code' },
                { title: 'Barcode', data: 'variation_barcode', name: 'variation_barcode' },
                { title: 'Retail', data: 'variation_retail_price', name: 'variation_retail_price' },
                { title: 'Cost', data: 'variation_cost_price', name: 'variation_cost_price' },
                { title: 'Wholesale', data: 'variation_wholesale_price', name: 'variation_wholesale_price' },
                { title: 'Minimum', data: 'minimum_selling_price', name: 'minimum_selling_price' },
                { title: 'Online', data: 'online_price', name: 'online_price' },
                { title: 'Weight', data: 'weight', name: 'weight' },
                {
                    title: 'Images',
                    data: 'variation_product_images',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        var html = '<div class="d-flex gap-2 flex-wrap">';
                        var i = 0;

                        if (!data || !$.isArray(data) || data.length === 0) {
                            return '<span class="badge bg-secondary">No images</span>';
                        }

                        for (i = 0; i < data.length; i++) {
                            html += '<img src="/' + data[i] + '" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">';
                        }
                        html += '</div>';

                        return html;
                    }
                },
                {
                    title: 'Action',
                    data: function (data) {
                        return '' +
                            '<button type="button" class="btn btn-warning btn-sm me-1" data-panel-id="' + data.id + '" onclick="tempEdit(this)"><i class="fa fa-edit"></i></button>' +
                            '<button type="button" class="btn btn-danger btn-sm" data-panel-id="' + data.id + '" onclick="tempDelete(this)"><i class="fa fa-trash"></i></button>';
                    },
                    orderable: false,
                    searchable: false
                }
            ]
        });

        resetVariationForm();
    });
</script>
@endsection
