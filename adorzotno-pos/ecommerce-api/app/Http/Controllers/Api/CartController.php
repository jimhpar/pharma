<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CartController extends BaseApiController
{
    /**
     * Sync cart - validate items and check stock
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $cartItems = $request->items;
            $validatedItems = [];
            $errors = [];
            $totalPrice = 0.0;

            foreach ($cartItems as $item) {
                $product = Product::query()
                    ->with(['productImages', 'sku.stockBalances'])
                    ->find($item['product_id']);

                if (!$product) {
                    $errors[] = "Product #{$item['product_id']} not found";
                    continue;
                }

                $quantityRequested = $item['quantity'];
                $this->decorateProductsWithStock($product);

                $quantityAvailable = (int) $product->available_stock;

                if ($quantityRequested > $quantityAvailable) {
                    $errors[] = "{$product->name}: Only {$quantityAvailable} in stock";
                    continue;
                }

                $itemPrice = $product->default_discount_type === 'percentage'
                    ? $product->selling_price - ($product->selling_price * $product->default_discount_value / 100)
                    : $product->selling_price - $product->default_discount_value;
                $itemTotal = $itemPrice * $quantityRequested;
                $totalPrice += $itemTotal;

                $validatedItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantityRequested,
                    'price' => $itemPrice,
                    'item_total' => $itemTotal,
                    'image' => $product->productImages->first()?->image_path ?? null,
                ];
            }

            return $this->success([
                'valid_items' => $validatedItems,
                'total' => $totalPrice,
                'errors' => $errors,
            ], 'Cart synced successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Cart sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Apply coupon code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $couponCode = $request->coupon_code;
            $totalAmount = round((float) $request->total_amount, 2);

            $coupon = Coupon::query()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($couponCode)])
                ->first();

            if ($coupon === null) {
                return $this->notFound('Coupon not found');
            }

            if (!$coupon->isCurrentlyValid()) {
                return $this->error(null, 'Coupon is not active or has expired', 400);
            }

            if ($coupon->min_order_amount !== null && $totalAmount < (float) $coupon->min_order_amount) {
                return $this->error([
                    'min_order_amount' => (float) $coupon->min_order_amount,
                ], 'Order total does not meet the coupon minimum amount', 400);
            }

            $discountAmount = $coupon->calculateDiscount($totalAmount);
            $finalTotal = round(max(0, $totalAmount - $discountAmount), 2);
            $discountPercentage = $totalAmount > 0
                ? round(($discountAmount / $totalAmount) * 100, 2)
                : 0;

            return $this->success([
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => (float) $coupon->discount_value,
                    'min_order_amount' => $coupon->min_order_amount !== null ? (float) $coupon->min_order_amount : null,
                    'max_discount_amount' => $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : null,
                    'usage_limit' => $coupon->usage_limit,
                    'used_count' => (int) $coupon->used_count,
                    'status' => $coupon->status,
                ],
                'discount_amount' => $discountAmount,
                'discount_percentage' => $discountPercentage,
                'final_total' => $finalTotal,
            ], 'Coupon applied successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Coupon validation failed: ' . $e->getMessage());
        }
    }
}
