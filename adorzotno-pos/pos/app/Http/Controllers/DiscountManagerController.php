<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountManagerController extends Controller
{
    public function show()
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name', 'parent_id']);
        return view('discount_manager.index', compact('categories'));
    }

    /** AJAX: load products for the grid */
    public function products(Request $request): JsonResponse
    {
        $categoryIds = array_filter(array_map('intval', (array) $request->input('category_ids', [])));
        $search      = trim((string) $request->get('q', ''));

        // Expand category IDs to include descendants
        $allCategoryIds = [];
        foreach ($categoryIds as $cid) {
            $allCategoryIds = array_merge($allCategoryIds, $this->categoryDescendantIds($cid));
        }
        $allCategoryIds = array_unique($allCategoryIds);

        $products = Product::query()
            ->select(['id', 'name', 'category_id', 'default_discount_type', 'default_discount_value'])
            ->with([
                'category:id,name',
                'sku' => fn ($q) => $q->select('id', 'product_id', 'sale_price', 'retail_price', 'cost_price', 'discount_type', 'discount_value')->where('status', 'active'),
            ])
            ->where('status', 'Active')
            ->whereHas('sku')
            ->when(!empty($allCategoryIds), fn ($q) => $q->whereIn('category_id', $allCategoryIds))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(200)
            ->get();

        return response()->json([
            'products' => $products->map(function ($p) {
                $sku        = $p->sku->first();
                $salePrice  = (float) ($sku?->getRawOriginal('sale_price') ?? $sku?->getRawOriginal('retail_price') ?? 0);
                $tp         = (float) ($sku?->getRawOriginal('cost_price') ?? 0);
                $discType   = $sku?->discount_type  ?? $p->default_discount_type  ?? null;
                $discValue  = (float) ($sku?->discount_value ?? $p->default_discount_value ?? 0);
                $finalPrice = $this->calcFinalPrice($salePrice, $tp, $discType, $discValue);

                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'category_name' => $p->category?->name ?? '—',
                    'sale_price'    => $salePrice,
                    'tp'            => $tp,
                    'discount_type' => $discType,
                    'discount_value'=> $discValue,
                    'final_price'   => $finalPrice,
                ];
            })->values(),
        ]);
    }

    /** Apply discount to selected products */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids'    => 'required|array|min:1',
            'product_ids.*'  => 'integer|exists:products,id',
            'discount_type'  => 'required|in:percent,amount',
            'discount_value' => 'required|numeric|min:0',
        ]);

        $updated = Product::whereIn('id', $validated['product_ids'])
            ->update([
                'default_discount_type'  => $validated['discount_type'],
                'default_discount_value' => $validated['discount_value'],
            ]);

        return response()->json([
            'success' => true,
            'message' => "Discount applied to {$updated} product(s).",
            'updated' => $updated,
        ]);
    }

    /** Remove discount from selected products */
    public function remove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $validated['product_ids'])
            ->update([
                'default_discount_type'  => null,
                'default_discount_value' => 0,
            ]);

        return response()->json(['success' => true, 'message' => 'Discount removed.']);
    }

    private function calcFinalPrice(float $salePrice, float $tp, ?string $type, float $value): float
    {
        if (!$type || $value <= 0) return $salePrice;
        // percent discount = % of TP (never of selling price/VAT)
        if ($type === 'percent') {
            $discountAmount = $tp > 0 ? round($tp * $value / 100, 2) : 0;
            return max(0, round($salePrice - $discountAmount, 2));
        }
        return max(0, round($salePrice - $value, 2));
    }

    private function categoryDescendantIds(int $id): array
    {
        $ids      = [$id];
        $children = Category::where('parent_id', $id)->pluck('id')->toArray();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->categoryDescendantIds($child));
        }
        return $ids;
    }
}
