<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends BaseApiController
{
    /**
     * Get personalized recommendations for user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if ($request->user() !== null) {
                $customer = $this->authenticatedCustomer();

                $purchasedProductIds = SalesOrderItem::query()
                    ->whereHas('order', function ($query) use ($customer) {
                        $query->where('customer_id', $customer?->id);
                    })
                    ->with('sku:id,product_id')
                    ->get()
                    ->pluck('sku.product_id')
                    ->filter()
                    ->values();

                $userCategories = SalesOrderItem::query()
                    ->whereHas('order', function ($query) use ($customer) {
                        $query->where('customer_id', $customer?->id);
                    })
                    ->with('sku.product:id,category_id')
                    ->get()
                    ->pluck('sku.product.category_id')
                    ->filter()
                    ->unique()
                    ->values();

                $recommendations = $this->storefrontProductsQuery()
                    ->when($userCategories->isNotEmpty(), fn (Builder $query) => $query->whereIn('category_id', $userCategories))
                    ->when($purchasedProductIds->isNotEmpty(), fn (Builder $query) => $query->whereNotIn('id', $purchasedProductIds))
                    ->inRandomOrder()
                    ->limit(10)
                    ->get();

                if ($recommendations->isEmpty()) {
                    $recommendations = $this->getTrendingProducts(10);
                }
            } else {
                // For guests, return trending products
                $recommendations = $this->getTrendingProducts(10);
            }

            $this->decorateProductsWithStock($recommendations);

            return $this->success([
                'recommendations' => $recommendations,
            ], 'Recommendations retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve recommendations: '.$e->getMessage());
        }
    }

    /**
     * Get trending products
     */
    public function trending(Request $request): JsonResponse
    {
        try {
            $limit = min($request->limit ?? 10, 50);
            $products = $this->getTrendingProducts($limit);
            $this->decorateProductsWithStock($products);

            return $this->success([
                'products' => $products,
            ], 'Trending products retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve trending products: '.$e->getMessage());
        }
    }

    /**
     * Get related products
     *
     * @param  string  $slug
     */
    public function related($slug, Request $request): JsonResponse
    {
        try {
            $priceExpression = $this->productPriceExpression();
            $product = $this->storefrontProductsQuery()
                ->where('slug', $slug)
                ->first();

            if (! $product) {
                return $this->notFound('Product not found');
            }

            $this->decorateProductsWithStock($product);

            // Get related products from same category with similar price range
            $minPrice = $product->selling_price * 0.7;
            $maxPrice = $product->selling_price * 1.3;

            $related = $this->storefrontProductsQuery()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->whereBetween(DB::raw($priceExpression), [$minPrice, $maxPrice])
                ->limit(10)
                ->get();

            if ($related->count() < 10) {
                $fallbackIds = $related->pluck('id')->push((int) $product->id)->all();

                $fallback = $this->storefrontProductsQuery()
                    ->whereNotIn('id', $fallbackIds)
                    ->where(function (Builder $query) use ($product) {
                        $query->where('category_id', $product->category_id)
                            ->orWhere('brand_id', $product->brand_id);
                    })
                    ->inRandomOrder()
                    ->limit(10 - $related->count())
                    ->get();

                $related = $related->concat($fallback)->values();
            }

            $this->decorateProductsWithStock($related);

            return $this->success([
                'product' => $product,
                'related' => $related,
            ], 'Related products retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve related products: '.$e->getMessage());
        }
    }

    /**
     * Helper method to get trending products
     *
     * @param  int  $limit
     * @return array
     */
    private function getTrendingProducts($limit = 10)
    {
        $topProductIds = SalesOrderItem::query()
            ->select('product_skus.product_id', DB::raw('SUM(sales_order_items.quantity) as total_quantity'))
            ->join('product_skus', 'product_skus.id', '=', 'sales_order_items.sku_id')
            ->groupBy('product_skus.product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->pluck('product_skus.product_id');

        if ($topProductIds->isNotEmpty()) {
            return $this->storefrontProductsQuery()
                ->whereIn('id', $topProductIds)
                ->get()
                ->sortBy(fn (Product $product) => array_search($product->id, $topProductIds->all(), true))
                ->values();
        }

        return $this->storefrontProductsQuery()
            ->orderByDesc('is_popular')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function storefrontProductsQuery(): Builder
    {
        return Product::query()
            ->with(['productImages', 'sku.images', 'sku.stockBalances', 'category', 'brand'])
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->where(function (Builder $query) {
                $query->whereNull('is_online_enabled')
                    ->orWhere('is_online_enabled', true);
            });
    }
}
