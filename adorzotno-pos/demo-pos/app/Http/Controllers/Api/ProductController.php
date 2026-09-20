<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
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
        $priceExpression = 'COALESCE(sale_price, base_price)';
        $query = $this->storefrontProductsQuery();

        // Filter by category
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->brand_id) {
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
            $query->where('stock_quantity', '>', 0);
        }

        // Sort
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $query->orderBy('stock_quantity', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginate
        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);

        return $this->success($products->toArray(), 'Products retrieved successfully', 200);
    }

    /**
     * Get product details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
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
            ->find($id);

        if (!$product) {
            return $this->notFound('Product not found');
        }

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
        $priceExpression = 'COALESCE(sale_price, base_price)';
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
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->orWhere('long_description', 'like', "%{$searchTerm}%");
        });

        // Apply same filters as index
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $query->where('stock_quantity', '>', 0);
        }

        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);

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
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function productsByCategory($id, Request $request): JsonResponse
    {
        $priceExpression = 'COALESCE(sale_price, base_price)';
        $category = Category::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->find($id);

        if (!$category) {
            return $this->notFound('Category not found');
        }

        $query = $this->storefrontProductsQuery()->where('category_id', $id);

        // Apply same sorting/filtering as index
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $query->where('stock_quantity', '>', 0);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $query->orderBy('stock_quantity', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);

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
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function productsByBrand($id, Request $request): JsonResponse
    {
        $priceExpression = 'COALESCE(sale_price, base_price)';
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
            ->find($id);

        if (!$brand) {
            return $this->notFound('Brand not found');
        }

        $query = $this->storefrontProductsQuery()->where('brand_id', $id);

        // Apply same sorting/filtering as index
        if ($request->min_price) {
            $query->whereRaw($priceExpression . ' >= ?', [$request->min_price]);
        }
        if ($request->max_price) {
            $query->whereRaw($priceExpression . ' <= ?', [$request->max_price]);
        }
        if ($request->in_stock) {
            $query->where('stock_quantity', '>', 0);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        if ($sortBy === 'selling_price') {
            $query->orderBy(DB::raw($priceExpression), $sortOrder);
        } elseif ($sortBy === 'quantity') {
            $query->orderBy('stock_quantity', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->per_page ?? 20, 100);
        $products = $query->paginate($perPage);

        return $this->success([
            'brand' => $brand,
            'products' => $products->toArray(),
        ], 'Products retrieved successfully', 200);
    }

    private function storefrontProductsQuery(): Builder
    {
        return Product::query()
            ->with([
                'productImages',
                'sku.images',
                'productWarnings' => function ($query) {
                    $query->where('status', 'active');
                },
                'category',
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
}
