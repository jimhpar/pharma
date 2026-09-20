<?php

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use App\Models\Coupon;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\ShipmentZone;
use App\Models\Sku;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends BaseApiController
{
    /**
     * Create order from cart
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->unauthorized('Not authenticated');
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'phone' => 'required|string',
            'coupon_code' => 'nullable|string',
            'shipment_zone_id' => [
                'nullable',
                'integer',
                Rule::exists('shipping_zones', 'id')->where('status', 'active'),
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $customer = $this->authenticatedCustomer(true);
            $branchId = $this->resolveCheckoutBranchId();
            $warehouse = $branchId !== null ? $this->resolveCheckoutWarehouse($branchId) : null;
            $cartItems = $request->items;
            $totalAmount = 0;
            $coupon = null;
            $couponDiscountAmount = 0.0;
            $shipmentZone = $request->filled('shipment_zone_id')
                ? ShipmentZone::query()->find((int) $request->shipment_zone_id)
                : null;
            $shippingFee = $shipmentZone ? (float) $shipmentZone->charge : 0.0;
            $products = Product::query()
                ->with(['productImages', 'sku.stockBalances'])
                ->whereIn('id', collect($cartItems)->pluck('product_id')->unique()->values())
                ->get()
                ->keyBy('id');

            if ($branchId === null) {
                DB::rollBack();

                return $this->error(null, 'No active branch is configured for online checkout', 400);
            }

            if ($warehouse === null) {
                DB::rollBack();

                return $this->error(null, 'No active warehouse is configured for online checkout', 400);
            }

            // Validate all items first
            foreach ($cartItems as $item) {
                $product = $products->get((int) $item['product_id']);

                if (! $product) {
                    DB::rollBack();

                    return $this->error(null, 'One or more items are not available', 400);
                }

                $quantityAvailable = $this->getProductAvailableStockForWarehouse($product, (int) $warehouse->id);

                if ((int) $item['quantity'] > $quantityAvailable) {
                    DB::rollBack();

                    return $this->error(null, "{$product->name}: Only {$quantityAvailable} in stock", 400);
                }
            }

            // Create order
            $orderNumber = 'ORD-'.date('YmdHis').'-'.$user->id;

            $order = SalesOrder::create([
                'branch_id' => $branchId,
                'warehouse_id' => (int) $warehouse->id,
                'order_no' => $orderNumber,
                'customer_id' => $customer?->id,
                'cashier_id' => $user->id,
                'sales_channel' => 'online',
                'order_date' => now(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'sub_total' => 0,
                'grand_total' => 0,
                'paid_total' => 0,
                'due_total' => 0,
                'shipping_fee' => $shippingFee,
                'customer_note' => $request->notes ?? null,
                'internal_note' => $this->buildInternalNote($request, $shipmentZone),
            ]);

            if ($customer !== null) {
                $customer->shipping_address = $request->shipping_address;
                $customer->phone = $request->phone;
                $customer->save();
            }

            // Create order items and update stock
            $subTotal = 0;
            foreach ($cartItems as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $sku = $this->selectCheckoutSku($product, $quantity, (int) $warehouse->id);

                if (! $sku) {
                    DB::rollBack();

                    return $this->error(null, "{$product->name}: No stockable SKU is available", 400);
                }

                $unitPrice = $product->default_discount_type === 'percent' && $product->default_discount_value > 0
                    ? $product->selling_price - ($product->selling_price * $product->default_discount_value / 100)
                    : $product->selling_price - $product->default_discount_value;
                $itemTotal = $unitPrice * $quantity;
                $subTotal += $itemTotal;
                $totalAmount += $itemTotal;

                $allocations = $this->deductCheckoutStock(
                    $sku,
                    (int) $branchId,
                    (int) $warehouse->id,
                    (int) $order->id,
                    $quantity,
                    (int) $user->id,
                    $orderNumber
                );

                foreach ($allocations as $allocation) {
                    $allocatedQuantity = (int) $allocation['quantity'];

                    SalesOrderItem::create([
                        'sales_order_id' => $order->id,
                        'sku_id' => $sku->id,
                        'warehouse_id' => (int) $warehouse->id,
                        'batch_id' => $allocation['batch_id'],
                        'quantity' => $allocatedQuantity,
                        'unit_price' => $unitPrice,
                        'cost_price' => (float) ($allocation['unit_cost'] ?? $sku->cost_price ?? 0),
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'line_total' => $unitPrice * $allocatedQuantity,
                        'returned_quantity' => 0,
                    ]);
                }
            }

            if ($request->filled('coupon_code')) {
                $couponCode = trim((string) $request->input('coupon_code'));
                $coupon = Coupon::query()
                    ->whereRaw('LOWER(code) = ?', [mb_strtolower($couponCode)])
                    ->lockForUpdate()
                    ->first();

                if ($coupon === null) {
                    DB::rollBack();

                    return $this->notFound('Coupon not found');
                }

                if (! $coupon->isCurrentlyValid()) {
                    DB::rollBack();

                    return $this->error(null, 'Coupon is not active or has expired', 400);
                }

                if ($coupon->min_order_amount !== null && $subTotal < (float) $coupon->min_order_amount) {
                    DB::rollBack();

                    return $this->error([
                        'min_order_amount' => (float) $coupon->min_order_amount,
                    ], 'Order total does not meet the coupon minimum amount', 400);
                }

                $couponDiscountAmount = $coupon->calculateDiscount($subTotal);
            }

            // Update order total
            $grandTotal = round(max(0, $totalAmount - $couponDiscountAmount) + $shippingFee, 2);
            $order->update([
                'grand_total' => $grandTotal,
                'sub_total' => $subTotal,
                'cart_discount_total' => $couponDiscountAmount,
                'due_total' => $grandTotal,
                'paid_total' => 0,
            ]);

            if ($coupon !== null && $couponDiscountAmount > 0) {
                $order->coupons()->attach($coupon->id, [
                    'discount_amount' => $couponDiscountAmount,
                ]);

                $coupon->increment('used_count');
            }

            DB::commit();

            return $this->success([
                'order' => $order->load(['items.sku.product', 'coupons', 'items.sku.product.reviews' => function ($query) {
                    $query->where(function ($reviewQuery) {
                        $reviewQuery->whereNull('status')
                            ->orWhereRaw('LOWER(status) = ?', ['approved']);
                    })->with('customer')->latest();
                },]),
                'order_number' => $orderNumber,
                'sub_total' => $subTotal,
                'discount_amount' => $couponDiscountAmount,
                'coupon' => $coupon ? $this->formatCouponForResponse($coupon, $couponDiscountAmount) : null,
                'shipping_fee' => $shippingFee,
                'total_amount' => $grandTotal,
            ], 'Order created successfully', 201);
        } catch (\RuntimeException $e) {
            DB::rollBack();

            return $this->error(null, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('Order creation failed: '.$e->getMessage());
        }
    }

    /**
     * Get user's orders
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $orders = SalesOrder::where('customer_id', $customer?->id)
            ->with(['items.sku.product.sku.stockBalances', 'items.sku.stockBalances', 'coupons', 'items.sku.product.reviews' => function ($query) {
                $query->where(function ($reviewQuery) {
                    $reviewQuery->whereNull('status')
                        ->orWhereRaw('LOWER(status) = ?', ['approved']);
                })->with('customer')->latest();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);
        $this->decorateOrdersWithStorefrontProductData($orders->getCollection());

        return $this->success($orders->toArray(), 'Orders retrieved successfully', 200);
    }

    /**
     * Get order details
     *
     * @param  int  $id
     */
    public function show($id): JsonResponse
    {
        if (request()->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $order = SalesOrder::with(['items.sku.product.sku.stockBalances', 'items.sku.stockBalances', 'coupons', 'items.sku.product.reviews' => function ($query) {
            $query->where(function ($reviewQuery) {
                $reviewQuery->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['approved']);
            })->with('customer')->latest();
        }])
            ->where('customer_id', $customer?->id)
            ->find($id);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        $this->decorateOrderWithStorefrontProductData($order);

        return $this->success([
            'order' => $order,
        ], 'Order retrieved successfully', 200);
    }

    /**
     * Get order invoice
     *
     * @param  int  $id
     */
    public function invoice($id): JsonResponse
    {
        if (request()->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $order = SalesOrder::with(['items.sku.product', 'coupons'])
            ->where('customer_id', $customer?->id)
            ->find($id);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        return $this->success([
            'order' => $order,
            'invoice' => [
                'invoice_no' => $order->invoice_no,
                'order_no' => $order->order_no,
                'issued_at' => optional($order->order_date)->toISOString(),
                'customer_name' => $order->customer?->name,
                'customer_phone' => $order->customer?->phone,
                'shipping_address' => $order->customer?->shipping_address,
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->sku?->product?->id,
                    'product_name' => $item->sku?->product?->name,
                    'sku_id' => $item->sku_id,
                    'sku_code' => $item->sku?->sku_code,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
                'sub_total' => (float) $order->sub_total,
                'discount_amount' => (float) $order->cart_discount_total,
                'coupons' => $order->coupons->map(fn (Coupon $coupon) => $this->formatCouponForResponse(
                    $coupon,
                    (float) ($coupon->pivot?->discount_amount ?? 0)
                ))->values(),
                'shipping_fee' => (float) $order->shipping_fee,
                'grand_total' => (float) $order->grand_total,
                'paid_total' => (float) $order->paid_total,
                'due_total' => (float) $order->due_total,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ],
            'invoice_url' => null,
        ], 'Invoice retrieved successfully', 200);
    }

    /**
     * Cancel order
     *
     * @param  int  $id
     */
    public function cancel($id, Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $order = SalesOrder::with('items.sku.product')
            ->where('customer_id', $customer?->id)
            ->find($id);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        if ($order->status !== 'pending') {
            return $this->error(null, 'Only pending orders can be cancelled', 400);
        }

        DB::beginTransaction();
        try {
            $order = SalesOrder::query()
                ->with('items.sku.product')
                ->where('customer_id', $customer?->id)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($order->status !== 'pending') {
                DB::rollBack();

                return $this->error(null, 'Only pending orders can be cancelled', 400);
            }

            foreach ($order->items as $item) {
                $this->restoreCheckoutStock($order, $item, (int) $request->user()->id);
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => $order->paid_total > 0 ? 'partial' : 'unpaid',
            ]);

            DB::commit();

            return $this->success([
                'order' => $order,
            ], 'Order cancelled successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('Order cancellation failed: '.$e->getMessage());
        }
    }

    private function resolveCheckoutBranchId(): ?int
    {
        return Branch::query()
            ->where(function ($query) {
                $query->whereNull('is_active')
                    ->orWhere('is_active', true);
            })
            ->value('id');
    }

    private function resolveCheckoutWarehouse(int $branchId): ?Warehouse
    {
        return Warehouse::query()
            ->where('branch_id', $branchId)
            ->where(function ($query) {
                $query->whereNull('is_active')
                    ->orWhere('is_active', true);
            })
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    private function selectCheckoutSku(Product $product, int $quantity, int $warehouseId): ?Sku
    {
        $skus = Sku::query()
            ->with('stockBalances')
            ->where('product_id', $product->id)
            ->get();

        return $skus
            ->sortByDesc(fn (Sku $sku) => $this->getSkuAvailableStockForWarehouse($sku, $warehouseId))
            ->first(fn (Sku $sku) => $this->getSkuAvailableStockForWarehouse($sku, $warehouseId) >= $quantity);
    }

    private function getProductAvailableStockForWarehouse(Product $product, int $warehouseId): int
    {
        if (! $product->relationLoaded('sku')) {
            $product->load('sku.stockBalances');
        }

        return (int) $product->sku->sum(fn (Sku $sku) => $this->getSkuAvailableStockForWarehouse($sku, $warehouseId));
    }

    private function getSkuAvailableStockForWarehouse(Sku $sku, int $warehouseId): int
    {
        if (! $sku->relationLoaded('stockBalances')) {
            $sku->load('stockBalances');
        }

        return (int) $sku->stockBalances
            ->where('warehouse_id', $warehouseId)
            ->sum(fn (StockBalance $balance) => max(
                0,
                (int) $balance->available_quantity - (int) $balance->reserved_quantity
            ));
    }

    private function deductCheckoutStock(
        Sku $sku,
        int $branchId,
        int $warehouseId,
        int $orderId,
        int $quantity,
        int $userId,
        string $orderNumber
    ): array {
        $remaining = $quantity;
        $allocations = [];
        $balances = StockBalance::query()
            ->with('batch')
            ->where('branch_id', $branchId)
            ->where('warehouse_id', $warehouseId)
            ->where('sku_id', $sku->id)
            ->whereRaw('(available_quantity - reserved_quantity) > 0')
            ->orderByRaw('batch_id IS NULL')
            ->orderBy('batch_id')
            ->lockForUpdate()
            ->get();

        foreach ($balances as $balance) {
            if ($remaining <= 0) {
                break;
            }

            $available = max(0, (int) $balance->available_quantity - (int) $balance->reserved_quantity);
            $deductQuantity = min($remaining, $available);

            if ($deductQuantity <= 0) {
                continue;
            }

            $balance->available_quantity = (int) $balance->available_quantity - $deductQuantity;
            $balance->updated_at = now();
            $balance->save();

            if ($balance->batch_id !== null) {
                InventoryBatch::query()
                    ->whereKey($balance->batch_id)
                    ->lockForUpdate()
                    ->decrement('available_quantity', $deductQuantity);
            }

            InventoryTransaction::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'sku_id' => (int) $sku->id,
                'batch_id' => $balance->batch_id,
                'reference_type' => 'sales_order',
                'reference_id' => $orderId,
                'movement_type' => 'sale',
                'quantity' => -$deductQuantity,
                'balance_after' => (int) $balance->available_quantity,
                'unit_cost' => $balance->batch?->purchase_price ?? $sku->cost_price ?? 0,
                'remarks' => 'Online checkout ' . $orderNumber,
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);

            $allocations[] = [
                'batch_id' => $balance->batch_id,
                'quantity' => $deductQuantity,
                'unit_cost' => $balance->batch?->purchase_price ?? $sku->cost_price ?? 0,
            ];

            $remaining -= $deductQuantity;
        }

        if ($remaining > 0) {
            throw new \RuntimeException("{$sku->product?->name}: Only " . ($quantity - $remaining) . ' in stock');
        }

        return $allocations;
    }

    private function restoreCheckoutStock(SalesOrder $order, SalesOrderItem $item, int $userId): void
    {
        $warehouseId = $item->warehouse_id ?: $order->warehouse_id;

        if ($warehouseId === null || $item->sku_id === null || $order->branch_id === null) {
            return;
        }

        $quantity = (int) $item->quantity;
        $balance = StockBalance::query()->lockForUpdate()->firstOrNew([
            'branch_id' => (int) $order->branch_id,
            'warehouse_id' => (int) $warehouseId,
            'sku_id' => (int) $item->sku_id,
            'batch_id' => $item->batch_id,
        ], [
            'available_quantity' => 0,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        $balance->available_quantity = (int) $balance->available_quantity + $quantity;
        $balance->updated_at = now();
        $balance->save();

        if ($item->batch_id !== null) {
            InventoryBatch::query()
                ->whereKey($item->batch_id)
                ->lockForUpdate()
                ->increment('available_quantity', $quantity);
        }

        InventoryTransaction::create([
            'branch_id' => (int) $order->branch_id,
            'warehouse_id' => (int) $warehouseId,
            'sku_id' => (int) $item->sku_id,
            'batch_id' => $item->batch_id,
            'reference_type' => 'sales_order',
            'reference_id' => (int) $order->id,
            'movement_type' => 'sale_cancel',
            'quantity' => $quantity,
            'balance_after' => (int) $balance->available_quantity,
            'unit_cost' => $item->cost_price,
            'remarks' => 'Online order cancellation ' . $order->order_no,
            'occurred_at' => now(),
            'created_by' => $userId,
        ]);
    }

    private function formatCouponForResponse(Coupon $coupon, float $discountAmount): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'discount_amount' => round($discountAmount, 2),
            'min_order_amount' => $coupon->min_order_amount !== null ? (float) $coupon->min_order_amount : null,
            'max_discount_amount' => $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : null,
            'usage_limit' => $coupon->usage_limit,
            'used_count' => (int) $coupon->used_count,
            'status' => $coupon->status,
        ];
    }

    private function decorateOrdersWithStorefrontProductData($orders): void
    {
        $orders->each(fn (SalesOrder $order) => $this->decorateOrderWithStorefrontProductData($order));
    }

    private function decorateOrderWithStorefrontProductData(SalesOrder $order): void
    {
        $order->items->each(function (SalesOrderItem $item) {
            if ($item->sku !== null) {
                $this->decorateSkuWithStock($item->sku);
            }

            if ($item->sku?->product !== null) {
                $this->decorateProductWithStock($item->sku->product);
            }
        });
    }

    private function buildInternalNote(Request $request, ?ShipmentZone $shipmentZone): string
    {
        $segments = [
            'sale type: Online',
            'shipping address: '.trim((string) $request->shipping_address),
            'phone: '.trim((string) $request->phone),
        ];

        if ($request->filled('shipment_zone_id')) {
            $segments[] = 'shipment zone id: '.$request->shipment_zone_id;
        }

        if ($shipmentZone !== null) {
            $segments[] = 'shipment zone: '.$shipmentZone->name;
        }

        return implode(' | ', $segments);
    }
}
