<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Branch;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Sku;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseApiController
{
    /**
     * Create order from cart
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkout(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'phone' => 'required|string',
            'shipment_zone_id' => 'nullable|integer|exists:shipping_zones,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $customer = $this->authenticatedCustomer(true);
            $branchId = $this->resolveCheckoutBranchId();
            $cartItems = $request->items;
            $totalAmount = 0;

            if ($branchId === null) {
                DB::rollBack();
                return $this->error(null, 'No active branch is configured for online checkout', 400);
            }

            // Validate all items first
            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || $item['quantity'] > $product->quantity) {
                    DB::rollBack();
                    return $this->error(null, 'One or more items are out of stock', 400);
                }
            }

            // Create order
            $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . auth()->id();
            
            $order = SalesOrder::create([
                'branch_id' => $branchId,
                'order_no' => $orderNumber,
                'customer_id' => $customer?->id,
                'cashier_id' => $user->id,
                'sales_channel' => SalesOrder::CHANNEL_ECOMMERCE,
                'order_date' => now(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'sub_total' => 0,
                'grand_total' => 0,
                'paid_total' => 0,
                'due_total' => 0,
                'shipping_fee' => 0,
                'customer_note' => $request->notes ?? null,
                'internal_note' => $this->buildInternalNote($request),
            ]);

            if ($customer !== null) {
                $customer->shipping_address = $request->shipping_address;
                $customer->phone = $request->phone;
                $customer->save();
            }

            // Create order items and update stock
            $subTotal = 0;
            foreach ($cartItems as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Get the first SKU for this product (or create if needed)
                $sku = Sku::where('product_id', $product->id)->first();
                if (!$sku) {
                    // If no SKU exists, create a default one
                    $sku = Sku::create([
                        'product_id' => $product->id,
                        'sku_code' => $product->id . '-001',
                        'product_code' => $product->id . '-001',
                        'online_price' => $product->selling_price,
                        'retail_price' => $product->selling_price,
                        'status' => 'active',
                    ]);
                }
                
                $itemTotal = $product->selling_price * $item['quantity'];
                $subTotal += $itemTotal;
                $totalAmount += $itemTotal;

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'sku_id' => $sku->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'cost_price' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $itemTotal,
                    'returned_quantity' => 0,
                ]);

                // Deduct stock
                $product->decrement('stock_quantity', $item['quantity']);
            }

            // Update order total
            $order->update([
                'grand_total' => $totalAmount,
                'sub_total' => $subTotal,
                'due_total' => $totalAmount,
                'paid_total' => 0,
            ]);

            DB::commit();

            return $this->success([
                'order' => $order->load('items.sku.product'),
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
            ], 'Order created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Order creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get user's orders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $orders = SalesOrder::where('customer_id', $customer?->id)
            ->with('items.sku.product')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return $this->success($orders->toArray(), 'Orders retrieved successfully', 200);
    }

    /**
     * Get order details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $order = SalesOrder::with('items.sku.product')
            ->where('customer_id', $customer?->id)
            ->find($id);

        if (!$order) {
            return $this->notFound('Order not found');
        }

        return $this->success([
            'order' => $order,
        ], 'Order retrieved successfully', 200);
    }

    /**
     * Get order invoice
     *
     * @param int $id
     * @return JsonResponse
     */
    public function invoice($id): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $order = SalesOrder::with('items.sku.product')
            ->where('customer_id', $customer?->id)
            ->find($id);

        if (!$order) {
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
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel($id, Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer();

        $order = SalesOrder::with('items.sku.product')
            ->where('customer_id', $customer?->id)
            ->find($id);

        if (!$order) {
            return $this->notFound('Order not found');
        }

        if ($order->status !== 'pending') {
            return $this->error(null, 'Only pending orders can be cancelled', 400);
        }

        DB::beginTransaction();
        try {
            // Restore stock
            foreach ($order->items as $item) {
                $item->sku?->product?->increment('stock_quantity', $item->quantity);
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
            return $this->serverError('Order cancellation failed: ' . $e->getMessage());
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

    private function buildInternalNote(Request $request): string
    {
        $segments = [
            'sale type: Online',
            'shipping address: ' . trim((string) $request->shipping_address),
            'phone: ' . trim((string) $request->phone),
        ];

        if ($request->filled('shipment_zone_id')) {
            $segments[] = 'shipment zone id: ' . $request->shipment_zone_id;
        }

        return implode(' | ', $segments);
    }
}
