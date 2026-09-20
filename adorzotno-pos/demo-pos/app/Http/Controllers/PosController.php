<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ShipmentZone;
use App\Models\Sku;
use App\Models\TaxRule;
use App\Models\Warehouse;
use App\Services\LoyaltyService;
use App\Services\OrderService;
use App\Services\PosInventoryService;
use App\Support\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected OrderService $orderService;
    protected PosInventoryService $posInventoryService;
    protected LoyaltyService $loyaltyService;
    private ?Warehouse $posWarehouse = null;

    public function __construct(OrderService $orderService, PosInventoryService $posInventoryService, LoyaltyService $loyaltyService)
    {
        $this->orderService = $orderService;
        $this->posInventoryService = $posInventoryService;
        $this->loyaltyService = $loyaltyService;
    }

    public function show()
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $brands = Brand::query()->orderBy('name')->get(['id', 'name']);
        $loyaltySettings = $this->loyaltyService->getSettings();
        $vatRule = TaxRule::query()
            ->where('status', 'active')
            ->latest('id')
            ->first();
        $shipmentZones = ShipmentZone::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name', 'charge']);

        $products = $this->buildPosProductQuery()
            ->latest()
            ->limit(20)
            ->get();

        $this->appendProductCardMeta($products);

        $warehouse = $this->resolvePosWarehouse();

        return view('pos.index', compact('products', 'categories', 'brands', 'shipmentZones', 'loyaltySettings', 'vatRule', 'warehouse'));
    }

    public function skuSearch(Request $request): JsonResponse
    {
        $query       = trim((string) $request->get('q'));
        $categoryIds = array_filter(array_map('intval', (array) $request->input('category_ids', [])));
        $brandId     = $request->integer('brand_id');
        $perPage     = max(1, min(50, $request->integer('per_page', 20)));
        $page        = max(1, $request->integer('page', 1));

        $paginator = $this->buildPosProductQuery($query, $categoryIds, $brandId)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $products = $paginator->getCollection();
        $this->appendProductCardMeta($products, $query);

        return response()->json([
            'data' => $this->toCompactJson($products),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function productDetails(Request $request, int $productId): JsonResponse
    {
        $query    = trim((string) $request->get('q'));
        $warehouse = $this->resolvePosWarehouse();
        $branchId  = $warehouse?->branch_id;

        $product = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'sku.stockBalances',
                'sku.branchPrices' => function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                    $q->where('is_active', true);
                },
                'sku.images',
                'sku.variationRelation.variation',
            ])
            ->where('status', 'Active')
            ->findOrFail($productId);

        $skus = $product->sku->map(function (Sku $sku) use ($product, $warehouse) {
            $availableStock = $this->getSkuAvailableStock($sku);
            $variationValues = $sku->variationRelation
                ->map(fn ($rel) => [
                    'id'    => $rel->variation?->id,
                    'type'  => $rel->variation?->type,
                    'value' => $rel->variation?->value,
                ])
                ->sortBy('type')
                ->filter(fn ($v) => !empty($v['id']) && !empty($v['type']))
                ->values();

            return [
                'id'                => $sku->id,
                'product_code'      => $sku->product_code,
                'display_price'     => $this->posInventoryService->getSkuSellingPrice($sku, $warehouse),
                'medicine_unit_price' => $sku->medicine_unit_price_for_pos,
                'units_per_strip'   => max(1, (int) ($sku->units_per_strip ?? 1)),
                'available_stock'   => $availableStock,
                'is_medicine'       => $this->isMedicineProduct($product),
                'is_in_stock'       => $availableStock > 0,
                'thumbnail_url'     => $sku->images->first()?->image
                    ? url($sku->images->first()->image)
                    : ($product->thumbnail_image ? url($product->thumbnail_image) : null),
                'variation_values'  => $variationValues,
                'combination_label' => $variationValues
                    ->map(fn ($v) => $v['type'] . ': ' . $v['value'])
                    ->implode(' | '),
                'variation_map'     => $variationValues
                    ->mapWithKeys(fn ($v) => [$v['type'] => $v['id']])
                    ->all(),
            ];
        })->values();

        $variationGroups = $skus
            ->flatMap(fn ($sku) => $sku['variation_values'])
            ->groupBy('type')
            ->map(fn ($group, $type) => [
                'type'   => $type,
                'values' => $group->unique('id')->sortBy('value')->values()->all(),
            ])
            ->values()
            ->all();

        $preferredSkuId = null;
        if ($query !== '') {
            $matched = $skus->first(fn ($sku) => strcasecmp((string) $sku['product_code'], $query) === 0);
            $preferredSkuId = $matched['id'] ?? null;
        }
        if ($preferredSkuId === null && $skus->where('available_stock', '>', 0)->count() === 1) {
            $preferredSkuId = $skus->firstWhere('available_stock', '>', 0)['id'] ?? null;
        }

        return response()->json([
            'id'               => $product->id,
            'name'             => $product->name,
            'type'             => $product->type,
            'category_name'    => $product->category?->name,
            'brand_name'       => $product->brand?->name,
            'thumbnail_url'    => $product->thumbnail_image ? url($product->thumbnail_image) : null,
            'variation_groups' => $variationGroups,
            'skus'             => $skus,
            'preferred_sku_id' => $preferredSkuId,
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────

    private function buildPosProductQuery(string $query = '', array $categoryIds = [], ?int $brandId = null): Builder
    {
        $branchId = $this->resolvePosWarehouse()?->branch_id;

        $allCategoryIds = [];
        foreach ($categoryIds as $cid) {
            $allCategoryIds = array_merge($allCategoryIds, $this->getCategoryDescendantIds($cid));
        }
        $allCategoryIds = array_unique($allCategoryIds);

        return Product::query()
            ->select(['id', 'category_id', 'brand_id', 'name', 'dosage_form', 'strength', 'coating_type', 'thumbnail_image', 'status'])
            ->with([
                'category:id,name',
                'brand:id,name',
                'sku:id,product_id,sku_code,barcode,retail_price,online_price,units_per_strip,medicine_unit_price,status',
                'sku.stockBalances:id,sku_id,warehouse_id,available_quantity,reserved_quantity',
                'sku.branchPrices' => function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                    $q->where('is_active', true);
                },
            ])
            ->where('status', 'Active')
            ->whereHas('sku')
            ->when(!empty($allCategoryIds), fn ($q) => $q->whereIn('category_id', $allCategoryIds))
            ->when($brandId,    fn ($q) => $q->where('brand_id', $brandId))
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sq) use ($query) {
                    $sq->where('name', 'like', "%{$query}%")
                        ->orWhereHas('sku', fn ($sq2) =>
                            $sq2->where('sku_code', 'like', "%{$query}%")
                                ->orWhere('barcode', 'like', "%{$query}%")
                        );
                });
            });
    }

    private function appendProductCardMeta(Collection $products, string $query = ''): void
    {
        $warehouse = $this->resolvePosWarehouse();

        foreach ($products as $product) {
            $skus       = $product->sku->sortBy('id')->values();
            $skuCount   = $skus->count();
            $firstSku   = $skus->first();

            $priceValues = $skus
                ->map(fn (Sku $sku) => $this->posInventoryService->getSkuSellingPrice($sku, $warehouse))
                ->filter(fn (float $p) => $p >= 0)
                ->values();

            $firstSkuPrice   = $firstSku ? $this->posInventoryService->getSkuSellingPrice($firstSku, $warehouse) : 0.0;
            $availableStock  = $skus->sum(fn (Sku $sku) => $this->getSkuAvailableStock($sku));
            $matchedSku      = $query !== ''
                ? $skus->first(fn (Sku $sku) => strcasecmp((string) $sku->product_code, $query) === 0)
                : null;

            $product->sku_count         = $skuCount;
            $product->available_stock   = $availableStock;
            $product->is_variant_product = $skuCount > 1;
            $product->is_medicine       = $this->isMedicineProduct($product);
            $product->thumbnail_url     = $product->thumbnail_image ? url($product->thumbnail_image) : null;
            $product->category_name     = $product->category?->name;
            $product->brand_name        = $product->brand?->name;
            $product->min_price         = $priceValues->min() ?? 0;
            $product->max_price         = $priceValues->max() ?? 0;
            $product->price_label       = $skuCount > 1
                ? Currency::format($firstSkuPrice)
                : Currency::format($product->min_price);
            $product->direct_sku_id     = $skuCount === 1 ? $firstSku?->id : null;
            $product->action_sku_id     = $matchedSku?->id ?? $product->direct_sku_id;
            $product->card_badge        = $skuCount > 1 ? $skuCount . ' Variants' : ($firstSku?->product_code ?? 'SKU');
            $product->mrp = $firstSku ? (float) ($firstSku->retail_price ?? 0) : null;
        }
    }

    /** Compact array used by POS card rendering */
    private function toCompactJson(Collection $products): array
    {
        return $products->map(fn ($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'thumbnail_url'    => $p->thumbnail_url,
            'card_badge'       => $p->card_badge,
            'price_label'      => $p->price_label,
            'available_stock'  => $p->available_stock,
            'sku_count'        => $p->sku_count,
            'category_id'      => $p->category_id,
            'brand_id'         => $p->brand_id,
            'category_name'    => $p->category_name,
            'brand_name'       => $p->brand_name,
            'is_variant_product' => $p->is_variant_product,
            'is_medicine'      => $p->is_medicine,
            'action_sku_id'    => $p->action_sku_id,
            'dosage_form'            => $p->dosage_form,
            'strength'               => $p->strength,
            'coating_type'           => $p->coating_type,
            'mrp'                    => $p->mrp,
            'product_sale_price'     => $p->sale_price,
            'default_discount_type'  => $p->default_discount_type,
            'default_discount_value' => $p->default_discount_value,
        ])->values()->all();
    }

    private function isMedicineProduct(Product $product): bool
    {
        $category = strtolower((string) $product->category?->name);

        return str_contains($category, 'medicine') || str_contains($category, 'ঔষধ');
    }

    private function getSkuAvailableStock(Sku $sku): int
    {
        $warehouseId = $this->resolvePosWarehouse()?->id;

        if ($sku->relationLoaded('stockBalances')) {
            return max(0, (int) $sku->stockBalances->sum(function ($sb) use ($warehouseId) {
                if ($warehouseId !== null && (int) $sb->warehouse_id !== (int) $warehouseId) {
                    return 0;
                }
                return max(0, (int) $sb->available_quantity - (int) $sb->reserved_quantity);
            }));
        }

        return $this->posInventoryService->getSkuAvailableStock($sku->id, $this->resolvePosWarehouse());
    }

    public function completeSale(Request $request)
    {
        $validated = $request->validate([
            'customer_id'          => 'nullable|exists:customers,id',
            'shipment_zone_id'     => 'nullable|exists:shipping_zones,id',
            'delivery_charge'      => 'nullable|numeric|min:0',
            'order_discount'       => 'nullable|numeric|min:0',
            'vat_amount'           => 'nullable|numeric|min:0',
            'vat_is_inclusive'      => 'nullable|boolean',
            'payment_method'       => 'nullable|string',
            'payments'             => 'nullable|array',
            'payments.*.method'    => 'required_with:payments|string',
            'payments.*.amount'    => 'required_with:payments|numeric|min:0.01',
            'payments.*.note'      => 'nullable|string|max:500',
            'sale_type'            => 'required|in:Sale,Credit Sale,Quotation,Draft,Suspend',
            'redeemed_points'      => 'nullable|integer|min:0',
            'customer_note'        => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->orderService->createOrder($validated);
            \Cart::clear();
            \Cart::clearCartConditions();

            return response()->json(['success' => true, 'sales_order' => $result['sales_order']]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Return loyalty info for a customer — used by POS to show points and calculate preview discount.
     */
    public function customerLoyaltyInfo(Request $request): JsonResponse
    {
        $customerId = $request->integer('customer_id');

        if (!$customerId) {
            return response()->json(['enabled' => false]);
        }

        $customer = Customer::query()->find($customerId);

        if (!$customer) {
            return response()->json(['enabled' => false]);
        }

        $settings = $this->loyaltyService->getSettings();

        if (!$settings['enabled']) {
            return response()->json(['enabled' => false]);
        }

        return response()->json([
            'enabled'            => true,
            'redemption_enabled' => $settings['redemption_enabled'],
            'loyalty_points'     => (int) $customer->loyalty_points,
            'point_value'        => $settings['point_value'],
            'is_member'          => (bool) $customer->is_member,
            'member_label'       => $customer->is_member ? 'Member' : 'Regular',
            'max_discount'       => round($customer->loyalty_points * $settings['point_value'], 2),
            'points_per_amount'  => $settings['points_per_amount'],
        ]);
    }

    private function resolvePosWarehouse(): ?Warehouse
    {
        if ($this->posWarehouse instanceof Warehouse) {
            return $this->posWarehouse;
        }
        $this->posWarehouse = $this->posInventoryService->resolveWarehouse();
        return $this->posWarehouse;
    }

    private function getCategoryDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $childIds = Category::where('parent_id', $categoryId)->pluck('id')->toArray();
        foreach ($childIds as $childId) {
            $ids = array_merge($ids, $this->getCategoryDescendantIds($childId));
        }
        return $ids;
    }
}
