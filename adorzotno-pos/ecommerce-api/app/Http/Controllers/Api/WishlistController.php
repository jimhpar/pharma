<?php

namespace App\Http\Controllers\Api;

use App\Models\Sku;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends BaseApiController
{
    /**
     * Get user's wishlist
     */
    public function index(): JsonResponse
    {
        $customer = $this->authenticatedCustomer();

        if ($customer === null) {
            return $this->success([
                'items' => [],
            ], 'Wishlist retrieved successfully', 200);
        }

        $wishlist = Wishlist::where('customer_id', $customer->id)
            ->with(['sku.product.sku.stockBalances', 'sku.stockBalances', 'sku.product.reviews' => function ($query) {
                    $query->where(function ($reviewQuery) {
                        $reviewQuery->whereNull('status')
                            ->orWhereRaw('LOWER(status) = ?', ['approved']);
                    })->with('customer')->latest();
                }])
            ->get();

        $wishlist->each(function (Wishlist $item) {
            if ($item->sku !== null) {
                $this->decorateSkuWithStock($item->sku);
            }

            if ($item->sku?->product !== null) {
                $this->decorateProductWithStock($item->sku->product);
            }
        });

        return $this->success([
            'items' => $wishlist,
        ], 'Wishlist retrieved successfully', 200);
    }

    /**
     * Add product to wishlist
     */
    public function add(Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $request->validate([
            'sku_id' => 'required|integer|exists:product_skus,id',
        ]);

        try {
            $customer = $this->authenticatedCustomer(true);
            $sku = Sku::with('product')->find($request->sku_id);

            if ($customer === null || ! $sku) {
                return $this->notFound('SKU not found');
            }

            // Check if already in wishlist
            $exists = Wishlist::where('customer_id', $customer->id)
                ->where('sku_id', $request->sku_id)
                ->exists();

            if ($exists) {
                return $this->error(null, 'SKU already in wishlist', 400);
            }

            Wishlist::create([
                'customer_id' => $customer->id,
                'sku_id' => $request->sku_id,
            ]);

            return $this->success(null, 'Item added to wishlist successfully', 201);
        } catch (\Exception $e) {
            return $this->serverError('Failed to add to wishlist: '.$e->getMessage());
        }
    }

    /**
     * Remove product from wishlist
     *
     * @param  int  $sku_id
     */
    public function remove($sku_id): JsonResponse
    {
        if (request()->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        try {
            $customer = $this->authenticatedCustomer();

            $deleted = Wishlist::where('customer_id', $customer?->id)
                ->where('sku_id', $sku_id)
                ->delete();

            if ($deleted) {
                return $this->success(null, 'Item removed from wishlist successfully', 200);
            }

            return $this->notFound('Wishlist item not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to remove from wishlist: '.$e->getMessage());
        }
    }
}
