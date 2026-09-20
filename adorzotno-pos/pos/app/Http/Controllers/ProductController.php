<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTemp;
use App\Models\ProductWarning;
use App\Models\Sku;
use App\Models\TaxRule;
use App\Models\Unit;
use App\Models\Variation;
use App\Models\VariationRelation;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use ImageTrait;

    public function show()
    {
        $categories = $this->getCategoryOptions();
        $brands     = Brand::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('product.index', compact('categories', 'brands'));
    }

    public function list(Request $request)
    {
        $products = Product::with(['category.parent', 'categories', 'brand', 'sku'])
            ->orderByDesc('id');

        if ($request->filled('search_query')) {
            $q = $request->search_query;
            $products->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhereHas('sku', fn ($s) => $s->where('sku_code', 'like', "%{$q}%")
                                                      ->orWhere('barcode', 'like', "%{$q}%"));
            });
        }

        $categoryIds = array_filter((array) $request->input('category_ids', []), fn ($v) => $v !== '' && $v !== null);
        if (!empty($categoryIds)) {
            $categoryIds = array_map('intval', $categoryIds);
            $products->where(function ($sq) use ($categoryIds) {
                $sq->whereIn('category_id', $categoryIds)
                   ->orWhereHas('categories', fn ($s) => $s->whereIn('categories.id', $categoryIds));
            });
        }

        $brandIds = array_filter((array) $request->input('brand_ids', []), fn ($v) => $v !== '' && $v !== null);
        if (!empty($brandIds)) {
            $products->whereIn('brand_id', array_map('intval', $brandIds));
        }

        if ($request->filled('product_type')) {
            $products->where('product_type', $request->product_type);
        }

        if ($request->filled('status')) {
            $products->where('status', $request->status);
        }

        return DataTables()->of($products)
            ->addColumn('brand', function (Product $product) {
                return $product->brand?->name ?? 'N/A';
            })
            ->addColumn('type_label', function (Product $product) {
                return $product->type_label ?? 'N/A';
            })
            ->addColumn('sku_summary', function (Product $product) {
                $skuCodes = $product->sku
                    ->pluck('sku_code')
                    ->filter()
                    ->values();

                if ($skuCodes->isEmpty()) {
                    return 'N/A';
                }

                if ($skuCodes->count() === 1) {
                    return $skuCodes->first();
                }

                return $skuCodes->take(2)->implode(', ') . ' +' . ($skuCodes->count() - 2);
            })
            ->addColumn('thumbnail_image', function (Product $product) {
                return $product->thumbnail_image ? url($product->thumbnail_image) : '';
            })
            ->addColumn('category', function (Product $product) {
                if ($product->categories->isNotEmpty()) {
                    return $product->categories->map(fn ($c) => $c->name)->implode(', ');
                }

                if ($product->category === null) {
                    return 'N/A';
                }

                $parentName = $product->category->parent?->name;

                return $parentName
                    ? $parentName . ' / ' . $product->category->name
                    : $product->category->name;
            })
            ->addColumn('status', fn (Product $product) => $product->status === 'active' ? 'Active' : 'Inactive')
            ->addColumn('raw_status', fn (Product $product) => $product->status)
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status'])
            ->make(true);
    }

    public function brandSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $brands = Brand::query()
            ->where('status', 'active')
            ->when($q !== '', fn ($qr) => $qr->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name']);

        return response()->json([
            'results' => $brands->map(fn ($b) => ['id' => $b->id, 'text' => $b->name]),
        ]);
    }

    public function generateSkuCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'existing_codes' => ['nullable', 'array'],
            'existing_codes.*' => ['nullable', 'string', 'max:100'],
        ]);

        $category = Category::query()->findOrFail($validated['category_id']);
        $prefix = $this->skuPrefixFromCategoryName($category->name);
        $pattern = '/^' . preg_quote($prefix, '/') . '-(\d{8})$/i';
        $maxNumber = 0;

        $candidateCodes = Sku::query()
            ->where('sku_code', 'like', $prefix . '-%')
            ->pluck('sku_code')
            ->merge(ProductTemp::query()
                ->where('session_id', session()->getId())
                ->where('variation_sku_code', 'like', $prefix . '-%')
                ->pluck('variation_sku_code'))
            ->merge(collect($validated['existing_codes'] ?? []));

        foreach ($candidateCodes as $code) {
            if (preg_match($pattern, trim((string) $code), $matches)) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }

        return response()->json([
            'sku_code' => $prefix . '-' . str_pad((string) ($maxNumber + 1), 8, '0', STR_PAD_LEFT),
            'prefix' => $prefix,
        ]);
    }

    public function create()
    {
        $category = $this->getCategoryOptions();
        $brand = collect(); // loaded via AJAX
        $units = Unit::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'short_name']);
        $taxRules = TaxRule::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $variation = Variation::query()->orderBy('type')->orderBy('value')->get();

        return view('product.create', compact('category', 'brand', 'units', 'taxRules', 'variation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = $this->baseProductRules();

        if ($this->isSingleSkuType($request->input('type'))) {
            $rules = array_merge($rules, $this->singleSkuRules());
        }

        if ($request->input('type') === 'Variation') {
            $rules = array_merge($rules, $this->variationDefaultsRules());
        }

        $validated = Validator::make($request->all(), $rules)->validate();

        if ($this->isSingleSkuType($validated['type'])) {
            $this->assertMinimumSellingPrice(
                $validated['retail_price'] ?? null,
                $validated['minimum_selling_price'] ?? null,
                'minimum_selling_price'
            );
        }

        $variationTemps = collect();
        if ($validated['type'] === 'Variation') {
            $variationTemps = ProductTemp::query()
                ->where('session_id', session()->getId())
                ->get();

            if ($variationTemps->isEmpty()) {
                Session::flash('error', 'Add at least one variation before submitting the product.');

                return redirect()->back()->withInput();
            }

            $this->assertTempSkuIdentifiersAreUnique($variationTemps);
            $this->assertTempMinimumSellingPrices($variationTemps);
            $this->assertTempVariationCombinationsAreUnique($variationTemps);
        }

        DB::beginTransaction();

        try {
            $product = Product::query()->create([
                'name' => $validated['name'],
                'sale_price'            => $request->input('sale_price')            ?: null,
                'default_discount_type' => $request->input('default_discount_type') ?: null,
                'default_discount_value'=> $request->input('default_discount_value') ?: 0,
                'dosage_form'           => $request->input('dosage_form')            ?: null,
                'strength'              => $request->input('strength')               ?: null,
                'coating_type'          => $request->input('coating_type')           ?: null,
                'generic_name'          => $request->input('generic_name')           ?: null,
                'slug' => $validated['slug'],
                'category_id' => $validated['categories'][0],
                'brand_id' => $validated['brand'] ?? null,
                'unit_id' => $validated['unit_id'] ?? null,
                'tax_rule_id' => $validated['tax_rule_id'] ?? null,
                'product_type' => $this->mapProductType($validated['type']),
                'short_description' => $validated['short_description'] ?? null,
                'long_description' => $validated['long_description'] ?? null,
                'thumbnail_image' => $this->save_image('productImage', $validated['thumbnail_image']),
                'status' => $validated['status'],
                'is_featured' => $request->boolean('is_featured'),
                'is_flash_deals' => $request->boolean('is_flash_deals'),
                'is_popular' => $request->boolean('is_popular'),
                'is_online_enabled' => $request->has('is_online_enabled')
                    ? $request->boolean('is_online_enabled')
                    : true,
                'is_pos_enabled' => $request->has('is_pos_enabled')
                    ? $request->boolean('is_pos_enabled')
                    : true,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if ($this->isSingleSkuType($validated['type'])) {
                $this->createSingleSku($request, $product, $validated);
            }

            if ($validated['type'] === 'Variation') {
                $this->createVariationSkusFromTemp($request, $product, $variationTemps, $validated);

                ProductTemp::query()
                    ->where('session_id', session()->getId())
                    ->delete();
            }

            $product->categories()->sync($validated['categories']);

            $this->syncProductWarnings($product, $validated['warnings'] ?? null);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        Session::flash('success', 'Product Created Successfully!');

        return redirect()->route('product.show');
    }

    public function edit($productId)
    {
        $categories = $this->getCategoryOptions();
        $units      = Unit::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'short_name']);
        $taxRules   = TaxRule::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $product    = Product::with([
            'sku',
            'sku.images',
            'sku.variationRelation.variation',
            'productWarnings',
            'categories:id,name',
        ])->findOrFail($productId);
        $variations = Variation::query()->select(['id', 'type', 'value'])->orderBy('type')->orderBy('value')->get()->groupBy('type');
        // Pre-selected brand for AJAX select2 (only load the one brand)
        $brands = $product->brand_id
            ? Brand::where('id', $product->brand_id)->get(['id', 'name'])
            : collect();

        return view('product.edit', compact('product', 'brands', 'categories', 'units', 'taxRules', 'variations'));
    }

    public function update(Request $request, $productId): RedirectResponse
    {
        $product = Product::with([
            'sku.images',
            'sku.variationRelation.variation',
        ])->findOrFail($productId);

        $payload = $this->prepareProductUpdatePayload($request);
        $nameRules = ['required', 'string', 'max:255'];
        $slugRules = ['required', 'string', 'max:255'];

        if (trim((string) ($payload['name'] ?? '')) !== (string) $product->name) {
            $nameRules[] = Rule::unique('products', 'name')->ignore($product->getKey(), $product->getKeyName());
        }

        if (trim((string) ($payload['slug'] ?? '')) !== (string) $product->slug) {
            $slugRules[] = Rule::unique('products', 'slug')->ignore($product->getKey(), $product->getKeyName());
        }

        $rules = [
            'name' => $nameRules,
            'slug' => $slugRules,
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'brand' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'tax_rule_id' => 'nullable|exists:tax_rules,id',
            'type' => 'required|in:Single,Variation,Combo,Service',
            'status' => 'required|in:active,inactive',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'generic_name' => 'nullable|string|max:255',
            'thumbnail_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'warnings' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_flash_deals' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'is_online_enabled' => 'nullable|boolean',
            'is_pos_enabled' => 'nullable|boolean',
        ];

        if ($this->isSingleSkuType($payload['type'] ?? null)) {
            $singleSkuId = $product->sku->first()?->id;
            $rules = array_merge($rules, $this->singleSkuRules($singleSkuId));
        }

        if (($payload['type'] ?? null) === 'Variation') {
            $rules = array_merge($rules, [
                'variants' => 'required|array|min:1',
                'variants.*.sku_id' => 'nullable|integer',
                'variants.*.sku_code' => 'required|string|max:100',
                'variants.*.barcode' => 'nullable|string|max:100',
                'variants.*.cost_price' => 'nullable|numeric|min:0',
                'variants.*.retail_price' => 'required|numeric|min:0',
                'variants.*.sale_price' => 'nullable|numeric|min:0',
                'variants.*.wholesale_price' => 'nullable|numeric|min:0',
                'variants.*.minimum_selling_price' => 'nullable|numeric|min:0',
                'variants.*.online_price' => 'nullable|numeric|min:0',
                'variants.*.weight' => 'nullable|numeric|min:0',
                'variants.*.units_per_strip' => 'nullable|integer|min:1|max:100000',
                'variants.*.medicine_unit_price' => 'nullable|numeric|min:0',
                'variants.*.rating' => 'nullable|numeric|min:0|max:5',
                'variants.*.dosage_details' => 'nullable|string',
                'variants.*.track_stock' => 'nullable|boolean',
                'variants.*.track_batch' => 'nullable|boolean',
                'variants.*.track_expiry' => 'nullable|boolean',
                'variants.*.status' => 'required|in:active,inactive',
                'variants.*.variation_value_ids' => 'required|array|min:1',
                'variants.*.variation_value_ids.*' => 'required|exists:variations,id',
                'variants.*.new_images' => 'nullable|array',
                'variants.*.new_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
                'variants.*.delete_image_ids' => 'nullable|array',
                'variants.*.delete_image_ids.*' => 'nullable|integer',
                'remove_variant_ids' => 'nullable|array',
                'remove_variant_ids.*' => 'nullable|integer',
            ]);
        }

        $validated = Validator::make($payload, $rules)->validate();

        if ($this->isSingleSkuType($validated['type'])) {
            $this->assertMinimumSellingPrice(
                $validated['retail_price'] ?? null,
                $validated['minimum_selling_price'] ?? null,
                'minimum_selling_price'
            );
        }

        if ($validated['type'] === 'Variation') {
            $this->assertVariantIdentifiersAreUnique($validated['variants']);
            $this->assertVariantMinimumSellingPrices($validated['variants']);
            $this->assertVariantVariationCombinationsAreUnique($validated['variants']);
        }

        DB::beginTransaction();

        try {
            $productThumbnail = $product->thumbnail_image;
            if (!empty($validated['thumbnail_image'])) {
                $this->deleteImage($product->thumbnail_image);
                $productThumbnail = $this->save_image('productImage', $validated['thumbnail_image']);
            }

            $product->update([
                'name' => $validated['name'],
                'sale_price'            => $request->input('sale_price')            ?: null,
                'default_discount_type' => $request->input('default_discount_type') ?: null,
                'default_discount_value'=> $request->input('default_discount_value') ?: 0,
                'dosage_form'           => $request->input('dosage_form')            ?: null,
                'strength'              => $request->input('strength')               ?: null,
                'coating_type'          => $request->input('coating_type')           ?: null,
                'generic_name'          => $request->input('generic_name')           ?: null,
                'slug' => $validated['slug'],
                'category_id' => $validated['categories'][0],
                'brand_id' => $validated['brand'] ?? null,
                'unit_id' => $validated['unit_id'] ?? null,
                'tax_rule_id' => $validated['tax_rule_id'] ?? null,
                'product_type' => $this->mapProductType($validated['type']),
                'short_description' => $validated['short_description'] ?? null,
                'long_description' => $validated['long_description'] ?? null,
                'thumbnail_image' => $productThumbnail,
                'status' => $validated['status'],
                'is_featured' => $request->boolean('is_featured'),
                'is_flash_deals' => $request->boolean('is_flash_deals'),
                'is_popular' => $request->boolean('is_popular'),
                'is_online_enabled' => $request->boolean('is_online_enabled'),
                'is_pos_enabled' => $request->boolean('is_pos_enabled'),
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
            ]);

            if ($this->isSingleSkuType($validated['type'])) {
                $this->syncSingleProduct($request, $product, $validated);
            }

            if ($validated['type'] === 'Variation') {
                $this->syncVariationProduct($request, $product, $validated);
            }

            $product->categories()->sync($validated['categories']);

            $this->syncProductWarnings($product, $validated['warnings'] ?? null);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        Session::flash('success', 'Product Updated Successfully!');

        return redirect()->route('product.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product = Product::with([
            'sku.images',
            'sku.variationRelation',
            'productImages',
        ])->findOrFail($validated['id']);

        DB::beginTransaction();

        try {
            $this->assertProductCanBeDeleted($product);

            foreach ($product->sku as $sku) {
                $this->deleteSkuResources($sku);
            }

            foreach ($product->productImages as $image) {
                if ($image->exists) {
                    $this->deleteProductImage($image);
                }
            }

            $this->deleteImage($product->thumbnail_image);
            $product->delete();

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        return response()->json(['success' => 'Product deleted successfully.']);
    }

    public function imageDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image_id' => 'required|exists:product_images,id',
        ]);

        $image = ProductImage::query()->findOrFail($validated['image_id']);
        $this->deleteProductImage($image);

        return response()->json(['success' => 'Image deleted successfully.']);
    }

    private function baseProductRules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products,name',
            'slug' => 'required|string|max:255|unique:products,slug',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'brand' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'tax_rule_id' => 'nullable|exists:tax_rules,id',
            'type' => 'required|in:Single,Variation,Combo,Service',
            'status' => 'required|in:active,inactive',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'generic_name' => 'nullable|string|max:255',
            'thumbnail_image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'warnings' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_flash_deals' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'is_online_enabled' => 'nullable|boolean',
            'is_pos_enabled' => 'nullable|boolean',
        ];
    }

    private function singleSkuRules(?int $ignoreSkuId = null): array
    {
        $skuCodeRule = ['required', 'string', 'max:100', Rule::unique('product_skus', 'sku_code')];
        $barcodeRule = ['nullable', 'string', 'max:100', Rule::unique('product_skus', 'barcode')];

        if ($ignoreSkuId !== null) {
            $skuCodeRule[3] = Rule::unique('product_skus', 'sku_code')->ignore($ignoreSkuId);
            $barcodeRule[3] = Rule::unique('product_skus', 'barcode')->ignore($ignoreSkuId);
        }

        return [
            'sku_code' => $skuCodeRule,
            'barcode' => $barcodeRule,
            'cost_price' => 'nullable|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'minimum_selling_price' => 'nullable|numeric|min:0',
            'online_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'units_per_strip' => 'nullable|integer|min:1|max:100000',
            'medicine_unit_price' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'dosage_details' => 'nullable|string',
            'track_stock' => 'nullable|boolean',
            'track_batch' => 'nullable|boolean',
            'track_expiry' => 'nullable|boolean',
            'sku_status' => 'required|in:active,inactive',
            'single_product_images' => 'nullable|array',
            'single_product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'delete_single_image_ids' => 'nullable|array',
            'delete_single_image_ids.*' => 'nullable|integer',
        ];
    }

    private function variationDefaultsRules(): array
    {
        return [
            'variation_track_stock' => 'nullable|boolean',
            'variation_track_batch' => 'nullable|boolean',
            'variation_track_expiry' => 'nullable|boolean',
            'variation_sku_status' => 'required|in:active,inactive',
        ];
    }

    private function createSingleSku(Request $request, Product $product, array $validated): void
    {
        $tracking = $this->resolveSkuTrackingFlags($request, $validated['type']);

        $sku = Sku::query()->create([
            'product_id' => $product->id,
            'sku_code' => $validated['sku_code'],
            'barcode' => $validated['barcode'] ?? null,
            'variant_name' => null,
            'cost_price' => $validated['cost_price'] ?? 0,
            'retail_price' => $validated['retail_price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'wholesale_price' => $validated['wholesale_price'] ?? null,
            'minimum_selling_price' => $validated['minimum_selling_price'] ?? null,
            'online_price' => $validated['online_price'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'units_per_strip' => max(1, (int) ($validated['units_per_strip'] ?? 1)),
            'medicine_unit_price' => $validated['medicine_unit_price'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'dosage_details' => $validated['dosage_details'] ?? null,
            'track_stock' => $tracking['track_stock'],
            'track_batch' => $tracking['track_batch'],
            'track_expiry' => $tracking['track_expiry'],
            'track_serial' => $tracking['track_serial'],
            'status' => $validated['sku_status'],
        ]);

        foreach ($request->file('single_product_images', []) as $image) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'image_path' => $this->save_image('productImage', $image),
                'alt_text' => $product->name . ' Image',
            ]);
        }
    }

    private function createVariationSkusFromTemp(Request $request, Product $product, $variationTemps, array $validated): void
    {
        foreach ($variationTemps as $variationTemp) {
            $variationIds = collect($variationTemp->variation_ids ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $sku = Sku::query()->create([
                'product_id' => $product->id,
                'sku_code' => $variationTemp->variation_sku_code ?: $variationTemp->variation_product_code,
                'barcode' => $variationTemp->variation_barcode,
                'variant_name' => $this->buildVariantName($variationIds),
                'cost_price' => $variationTemp->variation_cost_price ?? 0,
                'retail_price' => $variationTemp->variation_retail_price ?? 0,
                'sale_price' => $variationTemp->variation_sale_price ?? null,
                'wholesale_price' => $variationTemp->variation_wholesale_price ?? null,
                'minimum_selling_price' => $variationTemp->minimum_selling_price ?? null,
                'online_price' => $variationTemp->online_price ?? null,
                'weight' => $variationTemp->weight ?? null,
                'units_per_strip' => max(1, (int) ($variationTemp->variation_units_per_strip ?? 1)),
                'medicine_unit_price' => $variationTemp->variation_medicine_unit_price ?? null,
                'rating' => $variationTemp->rating ?? null,
                'dosage_details' => $variationTemp->dosage_details ?? null,
                'track_stock' => $request->boolean('variation_track_stock', true),
                'track_batch' => $request->boolean('variation_track_batch'),
                'track_expiry' => $request->boolean('variation_track_expiry'),
                'track_serial' => false,
                'status' => $validated['variation_sku_status'],
            ]);

            foreach ($variationIds as $variationId) {
                VariationRelation::query()->create([
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'variation_id' => $variationId,
                ]);
            }

            foreach (($variationTemp->variation_product_images ?? []) as $imagePath) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'image_path' => $imagePath,
                    'alt_text' => $product->name . ' Image',
                ]);
            }
        }
    }

    private function syncSingleProduct(Request $request, Product $product, array $validated): void
    {
        $singleSku = $product->sku->first();

        if ($singleSku === null) {
            $singleSku = new Sku();
            $singleSku->product_id = $product->id;
        }

        $extraSkus = $product->sku->filter(fn (Sku $sku) => $sku->id !== $singleSku->id);
        foreach ($extraSkus as $extraSku) {
            $this->deleteSkuResources($extraSku);
        }

        $tracking = $this->resolveSkuTrackingFlags($request, $validated['type']);

        $singleSku->fill([
            'product_id' => $product->id,
            'sku_code' => $validated['sku_code'],
            'barcode' => $validated['barcode'] ?? null,
            'variant_name' => null,
            'cost_price' => $validated['cost_price'] ?? 0,
            'retail_price' => $validated['retail_price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'wholesale_price' => $validated['wholesale_price'] ?? null,
            'minimum_selling_price' => $validated['minimum_selling_price'] ?? null,
            'online_price' => $validated['online_price'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'units_per_strip' => max(1, (int) ($validated['units_per_strip'] ?? 1)),
            'medicine_unit_price' => $validated['medicine_unit_price'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'dosage_details' => $validated['dosage_details'] ?? null,
            'track_stock' => $tracking['track_stock'],
            'track_batch' => $tracking['track_batch'],
            'track_expiry' => $tracking['track_expiry'],
            'track_serial' => $tracking['track_serial'],
            'status' => $validated['sku_status'],
        ]);
        $singleSku->save();

        $singleSku->variationRelation()->delete();

        $deleteImageIds = collect($request->input('delete_single_image_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($deleteImageIds)) {
            $imagesToDelete = ProductImage::query()
                ->where('product_id', $product->id)
                ->where('sku_id', $singleSku->id)
                ->whereIn('id', $deleteImageIds)
                ->get();

            foreach ($imagesToDelete as $image) {
                $this->deleteProductImage($image);
            }
        }

        foreach ($request->file('single_product_images', []) as $image) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'sku_id' => $singleSku->id,
                'image_path' => $this->save_image('productImage', $image),
                'alt_text' => $product->name . ' Image',
            ]);
        }
    }

    private function syncVariationProduct(Request $request, Product $product, array $validated): void
    {
        $existingSkus = $product->sku->keyBy('id');
        $removeVariantIds = collect($request->input('remove_variant_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id);

        foreach ($removeVariantIds as $skuId) {
            if ($existingSkus->has($skuId)) {
                $this->deleteSkuResources($existingSkus->get($skuId));
                $existingSkus->forget($skuId);
            }
        }

        foreach ($validated['variants'] as $variantData) {
            $sku = null;

            if (!empty($variantData['sku_id'])) {
                $sku = $existingSkus->get((int) $variantData['sku_id']);
            }

            if ($sku === null) {
                $sku = new Sku();
                $sku->product_id = $product->id;
            }

            $variationIds = collect($variantData['variation_value_ids'] ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $sku->fill([
                'product_id' => $product->id,
                'sku_code' => $variantData['sku_code'],
                'barcode' => $variantData['barcode'] ?? null,
                'variant_name' => $this->buildVariantName($variationIds),
                'cost_price' => $variantData['cost_price'] ?? 0,
                'retail_price' => $variantData['retail_price'],
                'sale_price' => $variantData['sale_price'] ?? null,
                'wholesale_price' => $variantData['wholesale_price'] ?? null,
                'minimum_selling_price' => $variantData['minimum_selling_price'] ?? null,
                'online_price' => $variantData['online_price'] ?? null,
                'weight' => $variantData['weight'] ?? null,
                'units_per_strip' => max(1, (int) ($variantData['units_per_strip'] ?? 1)),
                'medicine_unit_price' => $variantData['medicine_unit_price'] ?? null,
                'rating' => $variantData['rating'] ?? null,
                'dosage_details' => $variantData['dosage_details'] ?? null,
                'track_stock' => !empty($variantData['track_stock']),
                'track_batch' => !empty($variantData['track_batch']),
                'track_expiry' => !empty($variantData['track_expiry']),
                'track_serial' => false,
                'status' => $variantData['status'],
            ]);
            $sku->save();
            $existingSkus->forget($sku->id);

            $sku->variationRelation()->delete();
            foreach (array_unique($variationIds) as $variationId) {
                VariationRelation::query()->create([
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'variation_id' => $variationId,
                ]);
            }

            $deleteImageIds = collect($variantData['delete_image_ids'] ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!empty($deleteImageIds)) {
                $imagesToDelete = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->where('sku_id', $sku->id)
                    ->whereIn('id', $deleteImageIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    $this->deleteProductImage($image);
                }
            }

            foreach (($variantData['new_images'] ?? []) as $image) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'image_path' => $this->save_image('productImage', $image),
                    'alt_text' => $product->name . ' Image',
                ]);
            }
        }

        foreach ($existingSkus as $orphanedSku) {
            $this->deleteSkuResources($orphanedSku);
        }
    }

    private function deleteSkuResources(Sku $sku): void
    {
        $this->assertSkuCanBeDeleted($sku);

        foreach ($sku->branchPrices as $branchPrice) {
            $branchPrice->delete();
        }

        foreach ($sku->images as $image) {
            $this->deleteProductImage($image);
        }

        $sku->variationRelation()->delete();
        $sku->delete();
    }

    private function deleteProductImage(ProductImage $image): void
    {
        $this->deleteImage($image->image_path);
        $image->delete();
    }

    private function prepareProductUpdatePayload(Request $request): array
    {
        $payload = $request->all();

        if (($payload['type'] ?? null) !== 'Variation') {
            return $payload;
        }

        $payload['variants'] = collect($payload['variants'] ?? [])
            ->filter(fn ($variant) => $this->variantRowHasMeaningfulData($variant))
            ->values()
            ->all();

        return $payload;
    }

    private function variantRowHasMeaningfulData(array $variant): bool
    {
        if (!empty($variant['sku_id'])) {
            return true;
        }

        $fields = [
            $variant['sku_code'] ?? null,
            $variant['barcode'] ?? null,
            $variant['cost_price'] ?? null,
            $variant['retail_price'] ?? null,
            $variant['wholesale_price'] ?? null,
            $variant['minimum_selling_price'] ?? null,
            $variant['online_price'] ?? null,
            $variant['weight'] ?? null,
            $variant['units_per_strip'] ?? null,
            $variant['medicine_unit_price'] ?? null,
            $variant['rating'] ?? null,
            $variant['dosage_details'] ?? null,
        ];

        $hasFieldValue = collect($fields)->contains(function ($value) {
            return $value !== null && $value !== '';
        });

        $hasVariationValue = collect($variant['variation_value_ids'] ?? [])
            ->contains(fn ($value) => $value !== null && $value !== '');

        $hasImages = collect($variant['new_images'] ?? [])
            ->contains(fn ($value) => !empty($value));

        return $hasFieldValue || $hasVariationValue || $hasImages;
    }

    private function mapProductType(string $type): string
    {
        return match ($type) {
            'Variation' => Product::TYPE_VARIANT_PARENT,
            'Single' => Product::TYPE_STANDARD,
            'Combo' => Product::TYPE_COMBO,
            'Service' => Product::TYPE_SERVICE,
            default => $type,
        };
    }

    private function buildVariantName(array $variationIds): string
    {
        if (empty($variationIds)) {
            return '';
        }

        return Variation::query()
            ->whereIn('id', $variationIds)
            ->orderBy('type')
            ->get()
            ->map(fn (Variation $variation) => trim($variation->type . ': ' . $variation->value))
            ->implode(' | ');
    }

    private function assertTempSkuIdentifiersAreUnique($variationTemps): void
    {
        $errors = [];
        $seenSkuCodes = [];
        $seenBarcodes = [];

        foreach ($variationTemps as $variationTemp) {
            $skuCode = trim((string) ($variationTemp->variation_sku_code ?: $variationTemp->variation_product_code));
            $barcode = trim((string) ($variationTemp->variation_barcode ?? ''));

            if ($skuCode === '') {
                $errors['variation_sku_code'] = ['Every saved variation needs a SKU code.'];
            }

            $skuKey = strtolower($skuCode);
            if ($skuCode !== '' && in_array($skuKey, $seenSkuCodes, true)) {
                $errors['variation_sku_code'] = ['Variation SKU codes must be unique.'];
            }
            if ($skuCode !== '') {
                $seenSkuCodes[] = $skuKey;
            }

            if ($skuCode !== '' && Sku::query()->where('sku_code', $skuCode)->exists()) {
                $errors['variation_sku_code'] = ['One of the saved variation SKU codes already exists.'];
            }

            if ($barcode !== '') {
                $barcodeKey = strtolower($barcode);
                if (in_array($barcodeKey, $seenBarcodes, true)) {
                    $errors['variation_barcode'] = ['Variation barcodes must be unique.'];
                }
                $seenBarcodes[] = $barcodeKey;

                if (Sku::query()->where('barcode', $barcode)->exists()) {
                    $errors['variation_barcode'] = ['One of the saved variation barcodes already exists.'];
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertTempMinimumSellingPrices($variationTemps): void
    {
        foreach ($variationTemps as $variationTemp) {
            $this->assertMinimumSellingPrice(
                $variationTemp->variation_retail_price ?? null,
                $variationTemp->minimum_selling_price ?? null,
                'minimum_selling_price'
            );
        }
    }

    private function assertTempVariationCombinationsAreUnique($variationTemps): void
    {
        $seenCombinations = [];

        foreach ($variationTemps as $variationTemp) {
            $variationIds = collect($variationTemp->variation_ids ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if (empty($variationIds)) {
                continue;
            }

            $combinationKey = implode('-', $variationIds);
            if (in_array($combinationKey, $seenCombinations, true)) {
                throw ValidationException::withMessages([
                    'variation_value' => ['Each saved variation must have a unique combination of variation values.'],
                ]);
            }

            $seenCombinations[] = $combinationKey;
        }
    }

    private function assertVariantIdentifiersAreUnique(array $variants): void
    {
        $errors = [];
        $seenSkuCodes = [];
        $seenBarcodes = [];

        foreach ($variants as $index => $variant) {
            $skuCode = trim((string) ($variant['sku_code'] ?? ''));
            $barcode = trim((string) ($variant['barcode'] ?? ''));
            $skuId = isset($variant['sku_id']) ? (int) $variant['sku_id'] : null;

            if ($skuCode !== '') {
                $skuKey = strtolower($skuCode);
                if (in_array($skuKey, $seenSkuCodes, true)) {
                    $errors["variants.$index.sku_code"] = ['SKU code must be unique.'];
                }
                $seenSkuCodes[] = $skuKey;

                $skuQuery = Sku::query()->where('sku_code', $skuCode);
                if ($skuId) {
                    $skuQuery->where('id', '!=', $skuId);
                }
                if ($skuQuery->exists()) {
                    $errors["variants.$index.sku_code"] = ['SKU code already exists.'];
                }
            }

            if ($barcode !== '') {
                $barcodeKey = strtolower($barcode);
                if (in_array($barcodeKey, $seenBarcodes, true)) {
                    $errors["variants.$index.barcode"] = ['Barcode must be unique.'];
                }
                $seenBarcodes[] = $barcodeKey;

                $barcodeQuery = Sku::query()->where('barcode', $barcode);
                if ($skuId) {
                    $barcodeQuery->where('id', '!=', $skuId);
                }
                if ($barcodeQuery->exists()) {
                    $errors["variants.$index.barcode"] = ['Barcode already exists.'];
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertVariantMinimumSellingPrices(array $variants): void
    {
        $errors = [];

        foreach ($variants as $index => $variant) {
            if ($this->minimumSellingPriceExceedsRetail(
                $variant['retail_price'] ?? null,
                $variant['minimum_selling_price'] ?? null
            )) {
                $errors["variants.$index.minimum_selling_price"] = ['Minimum selling price cannot exceed retail price.'];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertVariantVariationCombinationsAreUnique(array $variants): void
    {
        $errors = [];
        $seenCombinations = [];

        foreach ($variants as $index => $variant) {
            $variationIds = collect($variant['variation_value_ids'] ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if (empty($variationIds)) {
                continue;
            }

            $combinationKey = implode('-', $variationIds);
            if (in_array($combinationKey, $seenCombinations, true)) {
                $errors["variants.$index.variation_value_ids"] = ['Each variant must have a unique combination of variation values.'];
            }

            $seenCombinations[] = $combinationKey;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertMinimumSellingPrice($retailPrice, $minimumSellingPrice, string $field): void
    {
        if ($this->minimumSellingPriceExceedsRetail($retailPrice, $minimumSellingPrice)) {
            throw ValidationException::withMessages([
                $field => ['Minimum selling price cannot exceed retail price.'],
            ]);
        }
    }

    private function minimumSellingPriceExceedsRetail($retailPrice, $minimumSellingPrice): bool
    {
        if ($retailPrice === null || $retailPrice === '' || $minimumSellingPrice === null || $minimumSellingPrice === '') {
            return false;
        }

        return (float) $minimumSellingPrice > (float) $retailPrice;
    }

    private function isSingleSkuType(?string $type): bool
    {
        return in_array($type, ['Single', 'Combo', 'Service'], true);
    }

    private function resolveSkuTrackingFlags(Request $request, string $type): array
    {
        if ($type === 'Service') {
            return [
                'track_stock' => false,
                'track_batch' => false,
                'track_expiry' => false,
                'track_serial' => false,
            ];
        }

        return [
            'track_stock' => $request->boolean('track_stock', true),
            'track_batch' => $request->boolean('track_batch'),
            'track_expiry' => $request->boolean('track_expiry'),
            'track_serial' => false,
        ];
    }

    private function assertProductCanBeDeleted(Product $product): void
    {
        foreach ($product->sku as $sku) {
            $this->assertSkuCanBeDeleted($sku);
        }
    }

    private function assertSkuCanBeDeleted(Sku $sku): void
    {
        $blockingRelations = [
            'purchase order items' => $sku->purchaseOrderItems()->count(),
            'sales order items' => $sku->salesOrderItems()->count(),
            'inventory transactions' => $sku->inventoryTransactions()->count(),
            'inventory batches' => $sku->inventoryBatches()->count(),
            'stock balances' => $sku->stockBalances()->count(),
            'inventory serials' => $sku->inventorySerials()->count(),
            'stock transfer items' => $sku->stockTransferItems()->count(),
            'inventory adjustment items' => $sku->inventoryAdjustmentItems()->count(),
            'supplier return items' => $sku->supplierReturnItems()->count(),
        ];

        foreach ($blockingRelations as $label => $count) {
            if ($count > 0) {
                throw ValidationException::withMessages([
                    'id' => ['This product cannot be deleted because SKU ' . $sku->sku_code . ' is used in ' . $label . '.'],
                ]);
            }
        }
    }

    private function getCategoryOptions()
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $grouped = $categories->groupBy(fn (Category $category) => $category->parent_id ?: 0);
        $ordered = collect();

        $walk = function ($parentId, string $prefix = '', int $level = 0) use (&$walk, $grouped, $ordered) {
            foreach ($grouped->get($parentId, collect())->sortBy('name') as $category) {
                $category->display_name = $prefix . $category->name;
                $category->level        = $level;
                $ordered->push($category);
                $walk($category->id, $prefix . $category->name . ' / ', $level + 1);
            }
        };

        $walk(0, '', 0);

        return $ordered->isNotEmpty() ? $ordered : $categories;
    }

    private function skuPrefixFromCategoryName(string $categoryName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName) ?: 'CAT', 0, 3));

        return str_pad($prefix, 3, 'X');
    }

    private function syncProductWarnings(Product $product, ?string $warnings): void
    {
        $product->productWarnings()->delete();

        foreach ($this->splitTextareaLines($warnings) as $warning) {
            ProductWarning::query()->create([
                'product_id' => $product->id,
                'warning' => $warning,
                'status' => 'active',
            ]);
        }
    }

    private function splitTextareaLines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
