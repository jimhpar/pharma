@php
    $selectedProductId = old('product_id', $branchPrice?->sku?->product_id);
    $selectedSkuId = old('sku_id', $branchPrice?->sku_id);
    $selectedBranchId = old('branch_id', $branchPrice?->branch_id);
    $selectedStatus = old('status', ($branchPrice?->is_active ?? true) ? 'active' : 'inactive');
@endphp

<div class="form-body">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="branch_id" class="form-label">Branch</label>
            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                <option value="">Select Branch</option>
                @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>
                    {{ $branch->name }}{{ $branch->is_active ? '' : ' (Inactive)' }}
                </option>
                @endforeach
            </select>
            @error('branch_id')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="product_id" class="form-label">Product</label>
            <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                <option value="">Select Product</option>
                @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) $selectedProductId === (string) $product->id)>
                    {{ $product->name }}
                </option>
                @endforeach
            </select>
            @error('product_id')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="sku_id" class="form-label">SKU</label>
            <select id="sku_id" name="sku_id" class="form-select @error('sku_id') is-invalid @enderror" required>
                <option value="">Select SKU</option>
                @foreach ($products as $product)
                    @foreach ($product->sku as $sku)
                    <option
                        value="{{ $sku->id }}"
                        data-product-id="{{ $product->id }}"
                        data-sku-code="{{ $sku->sku_code }}"
                        data-default-retail="{{ $sku->retail_price }}"
                        data-default-wholesale="{{ $sku->wholesale_price }}"
                        data-default-minimum="{{ $sku->minimum_selling_price }}"
                        data-default-online="{{ $sku->online_price }}"
                        @selected((string) $selectedSkuId === (string) $sku->id)
                    >
                        {{ $product->name }} | {{ $sku->sku_code }}{{ $sku->variant_name ? ' | ' . $sku->variant_name : '' }}
                    </option>
                    @endforeach
                @endforeach
            </select>
            @error('sku_id')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-12 mb-3">
            <div class="card border shadow-none mb-0">
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Default Retail</small>
                            <strong id="defaultRetailPrice">N/A</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Default Wholesale</small>
                            <strong id="defaultWholesalePrice">N/A</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Default Minimum</small>
                            <strong id="defaultMinimumPrice">N/A</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Default Online</small>
                            <strong id="defaultOnlinePrice">N/A</strong>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-3">Leave a field blank if that branch should continue using the SKU default for that price type.</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <label for="retail_price" class="form-label">Retail Price</label>
            <input type="number" step="0.01" min="0" id="retail_price" name="retail_price" class="form-control @error('retail_price') is-invalid @enderror" value="{{ old('retail_price', $branchPrice?->retail_price) }}" placeholder="Enter retail price">
            @error('retail_price')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label for="wholesale_price" class="form-label">Wholesale Price</label>
            <input type="number" step="0.01" min="0" id="wholesale_price" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" value="{{ old('wholesale_price', $branchPrice?->wholesale_price) }}" placeholder="Enter wholesale price">
            @error('wholesale_price')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label for="minimum_selling_price" class="form-label">Minimum Selling Price</label>
            <input type="number" step="0.01" min="0" id="minimum_selling_price" name="minimum_selling_price" class="form-control @error('minimum_selling_price') is-invalid @enderror" value="{{ old('minimum_selling_price', $branchPrice?->minimum_selling_price) }}" placeholder="Enter minimum selling price">
            @error('minimum_selling_price')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label for="online_price" class="form-label">Online Price</label>
            <input type="number" step="0.01" min="0" id="online_price" name="online_price" class="form-control @error('online_price') is-invalid @enderror" value="{{ old('online_price', $branchPrice?->online_price) }}" placeholder="Enter online price">
            @error('online_price')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
            </select>
            @error('status')
            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
            @enderror
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('branchPrice.show') }}" class="btn btn-light-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ isset($branchPrice) ? 'Update' : 'Save' }}</button>
        </div>
    </div>
</div>
