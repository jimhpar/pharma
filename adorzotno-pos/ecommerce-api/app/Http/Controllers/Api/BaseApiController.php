<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sku;
use App\Models\StockBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class BaseApiController extends Controller
{
    /**
     * Success response
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     */
    public function success($data = null, $message = 'Success', $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $statusCode);
    }

    /**
     * Error response
     *
     * @param  mixed  $errors
     * @param  string  $message
     * @param  int  $statusCode
     */
    public function error($errors = null, $message = 'Error', $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Validation error response
     *
     * @param  array  $errors
     * @param  string  $message
     */
    public function validationError($errors = [], $message = 'Validation failed'): JsonResponse
    {
        return $this->error($errors, $message, 422);
    }

    /**
     * Not found response
     *
     * @param  string  $message
     */
    public function notFound($message = 'Resource not found'): JsonResponse
    {
        return $this->error(null, $message, 404);
    }

    /**
     * Unauthorized response
     *
     * @param  string  $message
     */
    public function unauthorized($message = 'Unauthorized'): JsonResponse
    {
        return $this->error(null, $message, 401);
    }

    /**
     * Server error response
     *
     * @param  string  $message
     */
    public function serverError($message = 'Server error'): JsonResponse
    {
        return $this->error(null, $message, 500);
    }

    protected function applyLiveStockFilter(Builder $query): Builder
    {
        return $query->whereHas('sku.stockBalances', function (Builder $stockQuery) {
            $stockQuery->whereRaw('(available_quantity - reserved_quantity) > 0');
        });
    }

    protected function applyLiveStockSort(Builder $query, string $sortOrder): Builder
    {
        return $query
            ->select('products.*')
            ->selectSub(function ($subQuery) {
                $subQuery->from('product_skus')
                    ->join('stock_balances', 'stock_balances.sku_id', '=', 'product_skus.id')
                    ->whereColumn('product_skus.product_id', 'products.id')
                    ->selectRaw('COALESCE(SUM(CASE WHEN (stock_balances.available_quantity - stock_balances.reserved_quantity) > 0 THEN (stock_balances.available_quantity - stock_balances.reserved_quantity) ELSE 0 END), 0)');
            }, 'available_stock_sort')
            ->orderBy('available_stock_sort', $sortOrder);
    }

    protected function productPriceExpression(): string
    {
        return '(SELECT MIN(COALESCE(product_skus.online_price, product_skus.retail_price)) FROM product_skus WHERE product_skus.product_id = products.id)';
    }

    protected function decorateProductsWithStock(mixed $products): mixed
    {
        if ($products instanceof Product) {
            return $this->decorateProductWithStock($products);
        }

        if ($products instanceof AbstractPaginator) {
            $products->getCollection()->each(fn (Product $product) => $this->decorateProductWithStock($product));

            return $products;
        }

        if ($products instanceof EloquentCollection || $products instanceof Collection) {
            $products->each(fn (Product $product) => $this->decorateProductWithStock($product));
        }

        return $products;
    }

    protected function decorateProductWithStock(Product $product): Product
    {
        if (! $product->relationLoaded('sku')) {
            $product->load('sku.stockBalances');
        }

        $totalQuantity = 0;
        $reservedQuantity = 0;
        $availableQuantity = 0;
        $skuPrices = [];

        foreach ($product->sku as $sku) {
            $this->decorateSkuWithStock($sku);
            $stock = $sku->getAttribute('stock');
            $skuPrice = $sku->online_price ?? $sku->retail_price;

            $totalQuantity += $stock['total_quantity'];
            $reservedQuantity += $stock['reserved_quantity'];
            $availableQuantity += $stock['available_quantity'];

            if ($skuPrice !== null) {
                $skuPrices[] = (float) $skuPrice;
            }
        }

        $productStock = [
            'total_quantity' => $totalQuantity,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $availableQuantity,
            'in_stock' => $availableQuantity > 0,
        ];

        $product->setAttribute('stock', $productStock);
        $product->setAttribute('available_stock', $availableQuantity);
        $product->setAttribute('in_stock', $availableQuantity > 0);
        $product->setAttribute('selling_price', !empty($skuPrices) ? min($skuPrices) : 0.0);

        return $product;
    }

    protected function decorateSkuWithStock(Sku $sku): Sku
    {
        $stock = $this->skuStockSummary($sku);
        $skuPrice = $sku->online_price ?? $sku->retail_price;

        $sku->setAttribute('stock', $stock);
        $sku->setAttribute('available_stock', $stock['available_quantity']);
        $sku->setAttribute('in_stock', $stock['in_stock']);
        $sku->setAttribute('selling_price', $skuPrice !== null ? (float) $skuPrice : 0.0);
        $sku->unsetRelation('stockBalances');

        return $sku;
    }

    protected function skuStockSummary(Sku $sku): array
    {
        if (! $sku->relationLoaded('stockBalances')) {
            $sku->load('stockBalances');
        }

        $totalQuantity = 0;
        $reservedQuantity = 0;
        $availableQuantity = 0;

        foreach ($sku->stockBalances as $stockBalance) {
            /** @var StockBalance $stockBalance */
            $totalQuantity += (int) $stockBalance->available_quantity;
            $reservedQuantity += (int) $stockBalance->reserved_quantity;
            $availableQuantity += max(0, (int) $stockBalance->available_quantity - (int) $stockBalance->reserved_quantity);
        }

        return [
            'track_stock' => (bool) $sku->track_stock,
            'total_quantity' => $totalQuantity,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $availableQuantity,
            'in_stock' => $availableQuantity > 0,
        ];
    }

    protected function authenticatedCustomer(bool $createIfMissing = false): ?Customer
    {
        $user = request()->user();

        if ($user === null || $user->status !== 'active') {
            return null;
        }

        $customer = $user->customer;

        if ($customer !== null || ! $createIfMissing) {
            return $customer;
        }

        return Customer::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_id' => $user->id,
            'customer_code' => 'CUS-'.str_pad((string) ((int) Customer::query()->max('id') + 1), 6, '0', STR_PAD_LEFT),
            'opening_balance' => 0,
            'current_due' => 0,
            'loyalty_points' => 0,
            'status' => 'active',
        ]);
    }
}
