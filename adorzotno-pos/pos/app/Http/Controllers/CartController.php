<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryBatch;
use App\Models\Sku;
use App\Services\PosInventoryService;
use Darryldecode\Cart\CartCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected PosInventoryService $posInventoryService;

    public function __construct(PosInventoryService $posInventoryService)
    {
        $this->posInventoryService = $posInventoryService;
    }

    public function addToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|exists:product_skus,id',
            'quantity' => 'required|integer|min:1',
            'sale_unit' => 'nullable|in:unit,strip,medicine',
            'batch_id' => 'nullable|integer|exists:inventory_batches,id',
            'box_id' => 'nullable|integer|exists:inventory_boxes,id',
        ]);

        $sku = Sku::with(['product.brand', 'product.category', 'variationRelation.variation'])->findOrFail($validated['sku']);
        $product = $sku->product;
        $warehouse = $this->posInventoryService->resolveWarehouse();
        $selectedBatch = !empty($validated['batch_id'])
            ? InventoryBatch::query()
                ->where('sku_id', $sku->id)
                ->when($warehouse, fn ($q) => $q->where('warehouse_id', $warehouse->id))
                ->findOrFail((int) $validated['batch_id'])
            : null;

        if (!$product) {
            return response()->json([
                'message' => 'Product not found for this SKU.',
            ], 404);
        }

        $saleUnit = $this->resolveSaleUnit($sku, $validated['sale_unit'] ?? 'unit');
        $lineId = $this->cartLineId((int) $sku->id, $saleUnit, $selectedBatch?->id);
        $stockMultiplier = $this->stockMultiplier($sku, $saleUnit);
        $requestedQuantity = (int) $validated['quantity'];
        $existingCartItem = \Cart::get($lineId);
        $existingQuantity = $existingCartItem ? (int) $existingCartItem->quantity : 0;
        $stockAvailable = $selectedBatch
            ? $this->getAvailableBatchStock((int) $selectedBatch->id)
            : $this->getAvailableStock($sku->id);
        $alreadyRequested = $selectedBatch
            ? $this->stockQuantityInCartForBatch((int) $sku->id, (int) $selectedBatch->id, $lineId)
            : $this->stockQuantityInCartForSku((int) $sku->id, $lineId);
        $totalRequested = $alreadyRequested + (($existingQuantity + $requestedQuantity) * $stockMultiplier);

        if ($totalRequested > $stockAvailable) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
                'stockAvailable' => $stockAvailable,
            ], 400);
        }

        $stripPrice = $this->posInventoryService->getSkuSellingPrice($sku);
        $batchSalePrice = $selectedBatch && $selectedBatch->sale_price !== null && (float) $selectedBatch->sale_price > 0
            ? (float) $selectedBatch->sale_price
            : null;
        if ($batchSalePrice !== null) {
            $stripPrice = $batchSalePrice;
        }
        $price = $saleUnit === 'medicine'
            ? ($batchSalePrice !== null
                ? round($batchSalePrice / max(1, (int) ($sku->units_per_strip ?? 1)), 2)
                : $sku->medicine_unit_price_for_pos)
            : $stripPrice;

        // Resolve default discount from product level
        $discountType  = $sku->discount_type  ?? $product?->default_discount_type  ?? null;
        $discountValue = (float) ($sku->discount_value ?? $product?->default_discount_value ?? 0);

        // TP (trade/cost price) = discount base — discount never touches VAT
        $tp = (float) ($sku->getRawOriginal('cost_price') ?? 0);
        if ($discountType === 'percent') {
            $discountAmount = $tp > 0 ? round($tp * $discountValue / 100, 2) : 0;
        } elseif ($discountType === 'amount') {
            $discountAmount = min($discountValue, $price);
        } else {
            $discountAmount = 0;
        }
        $variationSummary = $sku->variationRelation
            ->map(function ($relation) {
                $variation = $relation->variation;

                if (!$variation || !$variation->type || !$variation->value) {
                    return null;
                }

                return $variation->type . ': ' . $variation->value;
            })
            ->filter()
            ->implode(' | ');

        if (!$existingCartItem) {
            \Cart::add([
                'id' => $lineId,
                'name' => $product->name,
                'price' => $price,
                'quantity' => $requestedQuantity,
                'associatedModel' => $product,
                'attributes' => [
                    'image' => $product->thumbnail_image ? url($product->thumbnail_image) : null,
                    'brand_name' => $product->brand?->name,
                    'category_name' => $product->category?->name,
                    'discount'         => $discountAmount,
                    'discount_amount'  => $discountAmount,
                    'discount_type'    => $discountType,
                    'discount_value'   => $discountValue,
                    'discount_percent' => ($discountType === 'percent') ? $discountValue
                        : ($price > 0 && $discountAmount > 0 ? round($discountAmount / $price * 100, 2) : 0),
                    'product_code' => $sku->product_code,
                    'sku_id' => $sku->id,
                    'sale_unit' => $saleUnit,
                    'sale_unit_label' => $this->saleUnitLabel($sku, $saleUnit),
                    'stock_multiplier' => $stockMultiplier,
                    'preferred_batch_id' => $selectedBatch?->id,
                    'batch_no' => $selectedBatch?->batch_no,
                    'batch_sale_price' => $batchSalePrice,
                    'batch_available_quantity' => $selectedBatch ? $stockAvailable : null,
                    'units_per_strip' => max(1, (int) ($sku->units_per_strip ?? 1)),
                    'strip_price' => $stripPrice,
                    'medicine_unit_price' => $sku->medicine_unit_price_for_pos,
                    'variation_summary' => $variationSummary,
                    'preferred_box_id' => $validated['box_id'] ?? null,
                ],
            ]);
        } else {
            \Cart::update($lineId, [
                'quantity' => [
                    'relative' => false,
                    'value' => $existingQuantity + $requestedQuantity,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Added to cart successfully.',
            'cartQuantity' => \Cart::getContent()->count(),
            'cart' => \Cart::getContent(),
            'total' => number_format((float) \Cart::getSubTotal(), 2, '.', ''),
        ], 200);
    }

    public function updateCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = \Cart::get($validated['sku']);
        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $requestedQuantity = (int) $validated['quantity'];
        $skuId = (int) ($cartItem->attributes->sku_id ?? $cartItem->id);
        $stockMultiplier = max(1, (int) ($cartItem->attributes->stock_multiplier ?? 1));
        $stockAvailable = $this->getAvailableStock($skuId);
        $otherStockInCart = $this->stockQuantityInCartForSku($skuId, (string) $validated['sku']);

        if (($otherStockInCart + ($requestedQuantity * $stockMultiplier)) > $stockAvailable) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
                'stockAvailable' => $stockAvailable,
            ], 400);
        }
       
        \Cart::update($validated['sku'], [
            'quantity' => [
                'relative' => false,
                'value' => $requestedQuantity,
            ],
        ]);

        $cartItem = \Cart::get($validated['sku']);
        if ($cartItem) {
            $attributes = method_exists($cartItem->attributes, 'toArray')
                ? $cartItem->attributes->toArray()
                : (array) $cartItem->attributes;
            $discountType = $this->normalizeDiscountType($attributes['discount_type'] ?? 'amount');
            $discountValue = (float) ($attributes['discount_value'] ?? $attributes['discount_amount'] ?? $attributes['discount'] ?? 0);
            $lineTotalBeforeDiscount = (float) $cartItem->price * (int) $cartItem->quantity;
            if ($discountType === 'amount' && $discountValue > $lineTotalBeforeDiscount) {
                $discountValue = $lineTotalBeforeDiscount;
            }
            $discountAmount = $this->calculateDiscountAmount($discountValue, $discountType, $lineTotalBeforeDiscount);

            $attributes['discount'] = $discountAmount;
            $attributes['discount_amount'] = $discountAmount;
            $attributes['discount_type'] = $discountType;
            $attributes['discount_value'] = $discountValue;
            $attributes['discount_percent'] = $discountType === 'percent'
                ? $discountValue
                : ($lineTotalBeforeDiscount > 0 && $discountAmount > 0 ? round($discountAmount / $lineTotalBeforeDiscount * 100, 2) : 0);

            \Cart::update($validated['sku'], [
                'attributes' => $attributes,
            ]);

            $cartItem = \Cart::get($validated['sku']);
        }
        $itemTotal = $cartItem ? $this->getCartLineTotal($cartItem) : 0;
        $discountAmount = $cartItem ? (float) ($cartItem->attributes->discount_amount ?? $cartItem->attributes->discount ?? 0) : 0;
        $discountValue = $cartItem ? (float) ($cartItem->attributes->discount_value ?? $discountAmount) : 0;

        $subTotal = (float) \Cart::getSubTotal();
        $grandTotal = (float) \Cart::getTotal();

        return response()->json([
            'cart' => \Cart::getContent(),
            'cartQuantity' => \Cart::getContent()->count(),
            'total' => number_format($subTotal, 2, '.', ''),
            'subtotal' => number_format($subTotal, 2, '.', ''),
            'grandtotal' => number_format($grandTotal, 2, '.', ''),
            'itemTotal' => number_format($itemTotal, 2, '.', ''),
            'discount' => number_format($discountValue, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
        ], 200);
    }

    public function updateItemDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string',
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:amount,percent',
        ]);

        $cartItem = \Cart::get($validated['sku']);

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found.',
            ], 404);
        }

        $discountValue = round((float) $validated['discount'], 2);
        $discountType = $this->normalizeDiscountType($validated['discount_type']);
        $price = (float) $cartItem->price;
        $lineTotal = $price * (int) $cartItem->quantity;

        if ($discountType === 'percent' && $discountValue > 100) {
            return response()->json([
                'message' => 'Product discount percent cannot exceed 100.',
            ], 422);
        }

        if ($discountType === 'amount' && $discountValue > $lineTotal) {
            return response()->json([
                'message' => 'Product discount cannot exceed item line total.',
            ], 422);
        }

        $discount = $this->calculateDiscountAmount($discountValue, $discountType, $lineTotal);

        $attributes = method_exists($cartItem->attributes, 'toArray')
            ? $cartItem->attributes->toArray()
            : (array) $cartItem->attributes;

        $attributes['discount'] = $discount;
        $attributes['discount_amount'] = $discount;
        $attributes['discount_type'] = $discountType;
        $attributes['discount_value'] = $discountValue;
        $attributes['discount_percent'] = $discountType === 'percent'
            ? $discountValue
            : ($lineTotal > 0 && $discount > 0 ? round($discount / $lineTotal * 100, 2) : 0);

        \Cart::update($validated['sku'], [
            'attributes' => $attributes,
        ]);

        $cartItem = \Cart::get($validated['sku']);

        return response()->json([
            'cart' => \Cart::getContent(),
            'cartQuantity' => \Cart::getContent()->count(),
            'total' => number_format((float) \Cart::getSubTotal(), 2, '.', ''),
            'subtotal' => number_format((float) \Cart::getSubTotal(), 2, '.', ''),
            'grandtotal' => number_format((float) \Cart::getTotal(), 2, '.', ''),
            'itemTotal' => number_format($this->getCartLineTotal($cartItem), 2, '.', ''),
            'discount' => number_format($discountValue, 2, '.', ''),
            'discount_amount' => number_format($discount, 2, '.', ''),
            'discount_type' => $discountType,
        ], 200);
    }

    public function removeCartItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required',
        ]);

        \Cart::remove($validated['sku']);

        if (\Cart::isEmpty()) {
            \Cart::clear();
            \Cart::clearCartConditions();
        }

        return response()->json([
            'cartQuantity' => \Cart::getContent()->count(),
            'cart' => \Cart::getContent(),
            'subtotal' => number_format((float) \Cart::getSubTotal(), 2, '.', ''),
            'grandtotal' => number_format((float) \Cart::getTotal(), 2, '.', ''),
        ], 200);
    }

    public function clearCart(): JsonResponse
    {
        \Cart::clear();
        \Cart::clearCartConditions();

        return response()->json([
            'cartQuantity' => 0,
            'cart' => [],
            'subTotal' => '0.00',
            'grandTotal' => '0.00',
        ], 200);
    }

    public function applyConditions(Request $request)
    {
        $validated = $request->validate([
            'delivery_charge' => 'nullable|numeric|min:0',
            'order_discount'  => 'nullable|numeric|min:0',
            'order_discount_type' => 'nullable|in:amount,percent',
            'order_discount_value' => 'nullable|numeric|min:0',
            'vat_amount'      => 'nullable|numeric|min:0',
            'vat_is_inclusive' => 'nullable|boolean',
        ]);

        \Cart::clearCartConditions();

        $itemDiscountTotal = $this->getCartItemDiscountTotal();

        if ($itemDiscountTotal > 0) {
            $itemDiscountCondition = new CartCondition([
                'name' => 'Item Discounts',
                'type' => 'discount',
                'target' => 'total',
                'value' => '-' . $itemDiscountTotal,
                'order' => 0,
            ]);
            \Cart::condition($itemDiscountCondition);
        }

        if (($validated['delivery_charge'] ?? 0) > 0) {
            $deliveryCondition = new CartCondition([
                'name'   => 'Delivery Charge',
                'type'   => 'delivery',
                'target' => 'total',
                'value'  => '+' . $validated['delivery_charge'],
                'order'  => 1,
            ]);
            \Cart::condition($deliveryCondition);
        }
       
        if (($validated['order_discount'] ?? 0) > 0) {
            $discountCondition = new CartCondition([
                'name'   => 'Order Discount',
                'type'   => 'discount',
                'target' => 'total',
                'value'  => '-' . $validated['order_discount'],
                'order'  => 2,
            ]);
            \Cart::condition($discountCondition);
        }

        if (($validated['vat_amount'] ?? 0) > 0 && !$request->boolean('vat_is_inclusive')) {
            $vatCondition = new CartCondition([
                'name'   => 'VAT',
                'type'   => 'tax',
                'target' => 'total',
                'value'  => '+' . $validated['vat_amount'],
                'order'  => 3,
            ]);
            \Cart::condition($vatCondition);
        }

        return response()->json([
            'total' => number_format((float) \Cart::getTotal(), 2, '.', ''),
        ]);
    }

    private function getAvailableStock(int $skuId): int
    {
        return $this->posInventoryService->getSkuAvailableStock($skuId);
    }

    private function cartLineId(int $skuId, string $saleUnit, ?int $batchId = null): string
    {
        return $skuId . '_' . $saleUnit . ($batchId ? '_b' . $batchId : '');
    }

    private function resolveSaleUnit(Sku $sku, string $saleUnit): string
    {
        if (!$this->isMedicineSku($sku)) {
            return 'unit';
        }

        return $saleUnit === 'medicine' ? 'medicine' : 'strip';
    }

    private function isMedicineSku(Sku $sku): bool
    {
        $category = strtolower((string) $sku->product?->category?->name);

        return str_contains($category, 'medicine') || str_contains($category, 'ঔষধ');
    }

    private function stockMultiplier(Sku $sku, string $saleUnit): int
    {
        return $saleUnit === 'strip' ? max(1, (int) ($sku->units_per_strip ?? 1)) : 1;
    }

    private function saleUnitLabel(Sku $sku, string $saleUnit): string
    {
        if ($saleUnit === 'strip') {
            return 'Strip (' . max(1, (int) ($sku->units_per_strip ?? 1)) . ')';
        }

        return $saleUnit === 'medicine' ? 'Medicine' : 'Unit';
    }

    private function stockQuantityInCartForSku(int $skuId, ?string $exceptLineId = null): int
    {
        return (int) \Cart::getContent()->sum(function ($cartItem) use ($skuId, $exceptLineId) {
            if ($exceptLineId !== null && (string) $cartItem->id === $exceptLineId) {
                return 0;
            }

            $itemSkuId = (int) ($cartItem->attributes->sku_id ?? $cartItem->id);
            if ($itemSkuId !== $skuId) {
                return 0;
            }

            return (int) $cartItem->quantity * max(1, (int) ($cartItem->attributes->stock_multiplier ?? 1));
        });
    }

    private function stockQuantityInCartForBatch(int $skuId, int $batchId, ?string $exceptLineId = null): int
    {
        return (int) \Cart::getContent()->sum(function ($cartItem) use ($skuId, $batchId, $exceptLineId) {
            if ($exceptLineId !== null && (string) $cartItem->id === $exceptLineId) {
                return 0;
            }

            $itemSkuId = (int) ($cartItem->attributes->sku_id ?? $cartItem->id);
            $itemBatchId = (int) ($cartItem->attributes->preferred_batch_id ?? 0);
            if ($itemSkuId !== $skuId || $itemBatchId !== $batchId) {
                return 0;
            }

            return (int) $cartItem->quantity * max(1, (int) ($cartItem->attributes->stock_multiplier ?? 1));
        });
    }

    private function getAvailableBatchStock(int $batchId): int
    {
        $batch = InventoryBatch::query()
            ->with('stockBalances')
            ->findOrFail($batchId);

        $stockBalance = $batch->stockBalances
            ->first(fn ($balance) => (int) $balance->warehouse_id === (int) $batch->warehouse_id);

        if ($stockBalance) {
            return max(0, (int) $stockBalance->available_quantity - (int) $stockBalance->reserved_quantity);
        }

        return max(0, (int) $batch->available_quantity);
    }

    private function getCartLineTotal($cartItem): float
    {
        $lineDiscount = (float) ($cartItem->attributes->discount_amount ?? $cartItem->attributes->discount ?? 0);

        return max(0, ((float) $cartItem->price * (int) $cartItem->quantity) - $lineDiscount);
    }

    private function getCartItemDiscountTotal(): float
    {
        return (float) \Cart::getContent()->sum(function ($cartItem) {
            return (float) ($cartItem->attributes->discount_amount ?? $cartItem->attributes->discount ?? 0);
        });
    }

    private function normalizeDiscountType(?string $discountType): string
    {
        return $discountType === 'percent' ? 'percent' : 'amount';
    }

    private function calculateDiscountAmount(float $discountValue, string $discountType, float $baseAmount): float
    {
        if ($discountType === 'percent') {
            return round($baseAmount * min($discountValue, 100) / 100, 2);
        }

        return round(min($discountValue, $baseAmount), 2);
    }
}
