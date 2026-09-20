<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseApiController
{
    /**
     * Get paginated list of products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $priceExpression = $this->productPriceExpression();
        $query = $this->storefrontProductsQuery();

        // Filter by category
        if ($request->category_slug) {
            $query->where(function (Builder $categoryQuery) use ($request) {
                $categoryQuery->whereHas('category', fn (Builder $query) => $query->where('slug', $request->category_slug))
                    ->orWhereHas('categories', fn (Builder $query) => $query->where('categories.slug', $request->category_slug));
            });
        } elseif ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->brand_slug) {
            $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $request->brand_slug));
        } elseif ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by price range
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }

        // Filter by stock status
        if ($request->in_stock) {
            $this->applyLiveStockFilter($query);
        }

        // Sort
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $this->applyLiveStockSort($query, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginate
        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);
        $this->decorateProductsWithStock($products);

        return $this->success($products->toArray(), 'Products retrieved successfully', 200);
    }

    /**
     * Get product details
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show($slug): JsonResponse
    {
        $product = $this->storefrontProductsQuery()
            ->with(['reviews' => function ($query) {
                $query->where(function ($reviewQuery) {
                    $reviewQuery->whereNull('status')
                        ->orWhereRaw('LOWER(status) = ?', ['approved']);
                })->with('customer')->latest();
            }, 'productWarnings' => function ($query) {
                $query->where('status', 'active');
            }])
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return $this->notFound('Product not found');
        }

        $this->decorateProductsWithStock($product);

        return $this->success([
            'product' => $product,
        ], 'Product retrieved successfully', 200);
    }

    /**
     * Search products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $priceExpression = $this->productPriceExpression();
        $searchTerm = $request->q ?? '';

        if (strlen($searchTerm) < 2) {
            return $this->validationError(['q' => 'Search term must be at least 2 characters'], 'Invalid search term');
        }

        $query = Product::query();
        $query = $this->storefrontProductsQuery();

        // Search in name and description
        $query->where(function ($searchQuery) use ($searchTerm) {
            $searchQuery
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('short_description', 'like', "%{$searchTerm}%")
                ->orWhere('long_description', 'like', "%{$searchTerm}%");
        });

        // Apply same filters as index
        if ($request->category_slug) {
            $query->where(function (Builder $categoryQuery) use ($request) {
                $categoryQuery->whereHas('category', fn (Builder $query) => $query->where('slug', $request->category_slug))
                    ->orWhereHas('categories', fn (Builder $query) => $query->where('categories.slug', $request->category_slug));
            });
        } elseif ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->brand_slug) {
            $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $request->brand_slug));
        } elseif ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $this->applyLiveStockFilter($query);
        }

        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);
        $this->decorateProductsWithStock($products);

        return $this->success($products->toArray(), 'Search results retrieved successfully', 200);
    }

    /**
     * Get all categories
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->with(['children' => function ($query) {
                $query->where(function (Builder $childQuery) {
                    $childQuery->whereNull('status')
                        ->orWhereRaw('LOWER(status) = ?', ['active']);
                })->orderBy('sort_order')->orderBy('name');
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'categories' => $categories,
        ], 'Categories retrieved successfully', 200);
    }

    /**
     * Get products by category
     *
     * @param string $slug
     * @param Request $request
     * @return JsonResponse
     */
    public function productsByCategory($slug, Request $request): JsonResponse
    {
        $priceExpression = $this->productPriceExpression();
        $category = Category::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            return $this->notFound('Category not found');
        }

        $query = $this->storefrontProductsQuery()
            ->where(function (Builder $categoryQuery) use ($category) {
                $categoryQuery->where('category_id', $category->id)
                    ->orWhereHas('categories', fn (Builder $query) => $query->where('categories.id', $category->id));
            });

        // Apply same sorting/filtering as index
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $this->applyLiveStockFilter($query);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $this->applyLiveStockSort($query, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);
        $this->decorateProductsWithStock($products);

        return $this->success([
            'category' => $category,
            'products' => $products->toArray(),
        ], 'Products retrieved successfully', 200);
    }

    /**
     * Get all brands
     *
     * @return JsonResponse
     */
    public function brands(): JsonResponse
    {
        $brands = Brand::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->with(['brandTags' => function ($query) {
                $query->where('status', 'active');
            }, 'brandCertifications' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'brands' => $brands,
        ], 'Brands retrieved successfully', 200);
    }

    /**
     * Get products by brand
     *
     * @param string $slug
     * @param Request $request
     * @return JsonResponse
     */
    public function productsByBrand($slug, Request $request): JsonResponse
    {
        $priceExpression = $this->productPriceExpression();
        $brand = Brand::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->with(['brandTags' => function ($query) {
                $query->where('status', 'active');
            }, 'brandCertifications' => function ($query) {
                $query->where('status', 'active');
            }])
            ->where('slug', $slug)
            ->first();

        if (!$brand) {
            return $this->notFound('Brand not found');
        }

        $query = $this->storefrontProductsQuery()->where('brand_id', $brand->id);

        // Apply same sorting/filtering as index
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $this->applyLiveStockFilter($query);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'most_popular') {
            $query->orderByDesc('is_popular')
                ->orderByDesc('created_at');
        } elseif ($sortBy === 'price_high_to_low') {
            $query->select('products.*')
                ->selectSub(function ($subQuery) {
                    $subQuery->from('product_skus')
                        ->selectRaw('MAX(COALESCE(online_price, retail_price))')
                        ->whereColumn('product_skus.product_id', 'products.id');
                }, 'sku_sort_price')
                ->orderByDesc('sku_sort_price');
        } elseif ($sortBy === 'price_low_to_high') {
            $query->select('products.*')
                ->selectSub(function ($subQuery) {
                    $subQuery->from('product_skus')
                        ->selectRaw('MIN(COALESCE(online_price, retail_price))')
                        ->whereColumn('product_skus.product_id', 'products.id');
                }, 'sku_sort_price')
                ->orderByRaw('sku_sort_price IS NULL')
                ->orderBy('sku_sort_price');
        } elseif ($sortBy === 'highest_rated') {
            $query->select('products.*')
                ->selectSub(function ($subQuery) {
                    $subQuery->from('product_skus')
                        ->selectRaw('MAX(rating)')
                        ->whereColumn('product_skus.product_id', 'products.id');
                }, 'sku_sort_rating')
                ->orderByDesc('sku_sort_rating');
        } elseif ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $this->applyLiveStockSort($query, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);
        $this->decorateProductsWithStock($products);

        return $this->success([
            'brand' => $brand,
            'products' => $products->toArray(),
        ], 'Products retrieved successfully', 200);
    }

    public function productsByProductType($productType, Request $request): JsonResponse
    {
        return $this->paginatedProductsResponse(
            $this->storefrontProductsQuery()->where('product_type', $productType),
            $request,
            'Products retrieved successfully'
        );
    }

    public function productsByGenericName($genericName, Request $request): JsonResponse
    {
        return $this->paginatedProductsResponse(
            $this->storefrontProductsQuery()->where('generic_name', $genericName),
            $request,
            'Products retrieved successfully'
        );
    }

    public function featuredProducts(Request $request): JsonResponse
    {
        return $this->paginatedProductsResponse(
            $this->storefrontProductsQuery()->where('is_featured', true),
            $request,
            'Featured products retrieved successfully'
        );
    }

    public function flashDealsProducts(Request $request): JsonResponse
    {
        return $this->paginatedProductsResponse(
            $this->storefrontProductsQuery()->where('is_flash_deals', true),
            $request,
            'Flash deals products retrieved successfully'
        );
    }

    private function storefrontProductsQuery(): Builder
    {
        return Product::query()
            ->with([
                'productImages',
                'sku.images',
                'sku.stockBalances',
                'reviews' => function ($query) {
                    $query->where(function ($reviewQuery) {
                        $reviewQuery->whereNull('status')
                            ->orWhereRaw('LOWER(status) = ?', ['approved']);
                    })->with('customer')->latest();
                },
                'productWarnings' => function ($query) {
                    $query->where('status', 'active');
                },
                'category',
                'categories',
                'brand.brandTags' => function ($query) {
                    $query->where('status', 'active');
                },
                'brand.brandCertifications' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->where(function (Builder $query) {
                $query->whereNull('is_online_enabled')
                    ->orWhere('is_online_enabled', true);
            });
    }

    private function paginatedProductsResponse(Builder $query, Request $request, string $message): JsonResponse
    {
        $priceExpression = $this->productPriceExpression();

        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $this->applyLiveStockFilter($query);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $this->applyLiveStockSort($query, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->per_page ?? 20, 100);

        $products = $query->paginate($perPage);
        $this->decorateProductsWithStock($products);

        return $this->success($products->toArray(), $message, 200);
    }
}
