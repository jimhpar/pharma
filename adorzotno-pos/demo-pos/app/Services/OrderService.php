<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\ShipmentZone;
use App\Models\Sku;
use Illuminate\Support\Facades\DB;
use RuntimeException;
// LoyaltyService is injected via constructor

class OrderService
{
    protected PosInventoryService $posInventoryService;
    protected LoyaltyService $loyaltyService;

    public function __construct(PosInventoryService $posInventoryService, LoyaltyService $loyaltyService)
    {
        $this->posInventoryService = $posInventoryService;
        $this->loyaltyService = $loyaltyService;
    }

    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            $cartItems = \Cart::getContent();
            $saleType = $this->resolveSaleType($data['sale_type'] ?? null);
            $modeConfig = $this->getSaleModeConfig($saleType);
            $warehouse = $this->posInventoryService->resolveWarehouse();

            if ($cartItems->isEmpty()) {
                throw new RuntimeException('Cart is empty.');
            }

            if ($warehouse === null) {
                throw new RuntimeException('No warehouse is configured for POS sales.');
            }

            if (empty($warehouse->branch_id)) {
                throw new RuntimeException('The POS warehouse must be linked to a branch.');
            }

            $stockRequestedBySku = [];
            foreach ($cartItems as $cartItem) {
                $skuId = (int) ($cartItem->attributes->sku_id ?? $cartItem->id);
                $stockQuantity = (int) $cartItem->quantity * max(1, (int) ($cartItem->attributes->stock_multiplier ?? 1));
                $stockRequestedBySku[$skuId] = ($stockRequestedBySku[$skuId] ?? 0) + $stockQuantity;
            }

            foreach ($stockRequestedBySku as $skuId => $stockQuantity) {
                $availableStock = $this->posInventoryService->getSkuAvailableStock($skuId, $warehouse);

                if (
                    $modeConfig['deduct_stock']
                    && !$warehouse->allow_negative_stock
                    && $stockQuantity > $availableStock
                ) {
                    throw new RuntimeException('Requested quantity exceeds available stock for SKU ' . $skuId . '.');
                }
            }

            $customer = !empty($data['customer_id'])
                ? Customer::query()->findOrFail($data['customer_id'])
                : null;
            $shipmentZoneId = $data['shipment_zone_id'] ?? null;
            $shipmentZone = $shipmentZoneId ? ShipmentZone::query()->find($shipmentZoneId) : null;
            $deliveryCharge = $shipmentZone ? (float) $shipmentZone->charge : (float) ($data['delivery_charge'] ?? 0);
            $orderDiscount = (float) ($data['order_discount'] ?? 0);
            $taxTotal = round((float) ($data['vat_amount'] ?? 0), 2);
            $isVatInclusive = (bool) ($data['vat_is_inclusive'] ?? false);
            $orderSubTotal = round((float) $cartItems->sum(function ($cartItem) {
                return (float) $cartItem->price * (int) $cartItem->quantity;
            }), 2);
            $itemDiscountTotal = round((float) $cartItems->sum(function ($cartItem) {
                return $this->getCartItemDiscount($cartItem);
            }), 2);
            $cartGrandTotal = max(0, round($orderSubTotal - $itemDiscountTotal - $orderDiscount + ($isVatInclusive ? 0 : $taxTotal) + $deliveryCharge, 2));

            // ── Loyalty: validate and calculate point discount ──────────────
            $loyaltySettings  = $this->loyaltyService->getSettings();
            $redeemedPoints   = 0;
            $pointDiscountAmt = 0.0;

            if ($customer && $loyaltySettings['enabled'] && $loyaltySettings['redemption_enabled']) {
                $requestedRedemption = (int) ($data['redeemed_points'] ?? 0);

                if ($requestedRedemption > 0 && in_array($saleType, ['Sale', 'Credit Sale'], true)) {
                    $this->loyaltyService->canRedeemPoints(
                        $customer,
                        $requestedRedemption,
                        $cartGrandTotal,
                        $loyaltySettings
                    );
                    $redeemedPoints   = $requestedRedemption;
                    $pointDiscountAmt = $this->loyaltyService->calculatePointDiscount($redeemedPoints, $loyaltySettings);
                }
            }

            // Payable amount after point discount
            $payableAmount = max(0, round($cartGrandTotal - $pointDiscountAmt, 2));
            $grandTotal    = $cartGrandTotal; // stored original cart total

            $payments = $this->prepareCheckoutPayments($data, $saleType, $payableAmount);
            $paidTotal = $payments['paid_total'];
            $dueTotal = max(0, $payableAmount - $paidTotal);
            $cashierId = auth()->id() ?? 1;
            $skuMap = Sku::query()
                ->whereIn('id', $cartItems->map(fn ($item) => (int) ($item->attributes->sku_id ?? $item->id))->all())
                ->get()
                ->keyBy('id');

            if ($saleType === 'Credit Sale' && !$customer) {
                throw new RuntimeException('Please select a customer before completing a credit sale.');
            }

            if ($dueTotal > 0 && !$customer) {
                throw new RuntimeException('A customer must be selected when the sale has an outstanding due amount.');
            }

            if ($customer && $customer->status !== 'active') {
                throw new RuntimeException('The selected customer is inactive and cannot be used for a sale.');
            }

            if ($customer && $dueTotal > 0 && $customer->credit_limit !== null) {
                $currentOutstanding = CustomerLedger::currentBalanceForCustomer((int) $customer->id);
                $projectedOutstanding = round($currentOutstanding + $dueTotal, 2);
                $hasOverridePermission = auth()->user()?->hasPermissionSlug('sales.credit_limit_override', (int) $warehouse->branch_id) ?? false;

                if (!$hasOverridePermission && $projectedOutstanding - (float) $customer->credit_limit > 0.00001) {
                    throw new RuntimeException(sprintf(
                        'Customer credit limit exceeded. Limit: %s, Current Due: %s, Projected Due: %s.',
                        number_format((float) $customer->credit_limit, 2, '.', ''),
                        number_format($currentOutstanding, 2, '.', ''),
                        number_format($projectedOutstanding, 2, '.', '')
                    ));
                }
            }

            $salesOrder = SalesOrder::query()->create([
                'branch_id'            => (int) $warehouse->branch_id,
                'warehouse_id'         => (int) $warehouse->id,
                'customer_id'          => $customer?->id,
                'cashier_id'           => $cashierId,
                'shift_id'             => null,
                'sales_channel'        => SalesOrder::CHANNEL_POS,
                'order_no'             => $this->generateSalesOrderNumber($warehouse->branch),
                'invoice_no'           => $this->generateSalesInvoiceNumber($saleType, $warehouse->branch),
                'order_date'           => now(),
                'status'               => $modeConfig['sales_order_status'],
                'payment_status'       => $this->resolveSalesPaymentStatus($saleType, $payableAmount, $paidTotal),
                'fulfillment_status'   => $modeConfig['fulfillment_status'],
                'sub_total'            => $orderSubTotal,
                'item_discount_total'  => $itemDiscountTotal,
                'cart_discount_total'  => $orderDiscount,
                'tax_total'            => $taxTotal,
                'shipping_fee'         => $deliveryCharge,
                'other_charge_total'   => 0,
                'grand_total'          => $grandTotal,
                'paid_total'           => $paidTotal,
                'due_total'            => $dueTotal,
                'change_amount'        => 0,
                'customer_note'        => $data['customer_note'] ?? null,
                'internal_note'        => $this->buildInternalNote($saleType, $payments['summary_label'], $shipmentZone?->name),
                'earned_points'        => 0,
                'redeemed_points'      => $redeemedPoints,
                'point_discount_amount'=> $pointDiscountAmt,
            ]);

            CustomerLedger::syncSalesOrderEntry($salesOrder);

            foreach ($cartItems as $cartData) {
                $skuId = (int) ($cartData->attributes->sku_id ?? $cartData->id);
                $sku = $skuMap->get($skuId);
                $displayQuantity = (int) $cartData->quantity;
                $stockMultiplier = max(1, (int) ($cartData->attributes->stock_multiplier ?? 1));
                $quantity = $displayQuantity * $stockMultiplier;
                $unitPrice = round((float) $cartData->price / $stockMultiplier, 2);
                $lineDiscountTotal = $this->getCartItemDiscount($cartData);
                $remainingDiscount = $lineDiscountTotal;
                $remainingQuantity = $quantity;

                if ($modeConfig['deduct_stock']) {
                    $preferredBoxId = !empty($cartData->attributes->preferred_box_id)
                        ? (int) $cartData->attributes->preferred_box_id
                        : null;
                    $allocations = $this->posInventoryService->deductSkuStockForSale(
                        $skuId,
                        (int) $warehouse->branch_id,
                        (int) $warehouse->id,
                        (int) $salesOrder->id,
                        $quantity,
                        $cashierId,
                        (string) $salesOrder->order_no,
                        $preferredBoxId
                    );

                    foreach ($allocations as $allocation) {
                        $allocatedQuantity = (int) $allocation['quantity'];
                        $allocatedDiscount = $remainingQuantity > 0
                            ? round($remainingDiscount * ($allocatedQuantity / $remainingQuantity), 2)
                            : 0;
                        $remainingDiscount = max(0, round($remainingDiscount - $allocatedDiscount, 2));
                        $remainingQuantity = max(0, $remainingQuantity - $allocatedQuantity);

                        $salesOrder->items()->create([
                            'sku_id' => $skuId,
                            'warehouse_id' => (int) $warehouse->id,
                            'batch_id' => $allocation['batch_id'],
                            'quantity' => $allocatedQuantity,
                            'unit_price' => $unitPrice,
                            'cost_price' => (float) ($allocation['unit_cost'] ?? $sku?->cost_price ?? 0),
                            'discount_amount' => $allocatedDiscount,
                            'tax_amount' => 0,
                            'line_total' => $this->calculateLineTotal($unitPrice, $allocatedQuantity, $allocatedDiscount),
                            'returned_quantity' => 0,
                        ]);
                    }
                } else {
                    $salesOrder->items()->create([
                        'sku_id' => $skuId,
                        'warehouse_id' => (int) $warehouse->id,
                        'batch_id' => null,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'cost_price' => (float) ($sku?->cost_price ?? 0),
                        'discount_amount' => $lineDiscountTotal,
                        'tax_amount' => 0,
                        'line_total' => $this->calculateLineTotal($unitPrice, $quantity, $lineDiscountTotal),
                        'returned_quantity' => 0,
                    ]);
                }
            }

            foreach ($payments['entries'] as $index => $paymentEntry) {
                $payment = Payment::query()->create([
                    'sales_order_id' => (int) $salesOrder->id,
                    'customer_id' => $customer?->id,
                    'branch_id' => (int) $warehouse->branch_id,
                    'payment_direction' => 'in',
                    'payment_purpose' => 'sale',
                    'payment_method' => $paymentEntry['payment_method'],
                    'amount' => $paymentEntry['amount'],
                    'payment_date' => now(),
                    'note' => $paymentEntry['note'] ?: $this->buildCheckoutPaymentNote($saleType, $index + 1, count($payments['entries'])),
                    'received_by' => $cashierId,
                ]);

                CustomerLedger::syncCollectionEntry($payment);
            }

            // ── Loyalty: award earned points and update membership ──────────
            if ($customer && $loyaltySettings['enabled'] && in_array($saleType, ['Sale', 'Credit Sale'], true)) {
                // Redeem first (already validated above); points stored on order
                if ($redeemedPoints > 0) {
                    $this->loyaltyService->redeemPoints($customer, $salesOrder, $redeemedPoints, $loyaltySettings);
                    $customer->refresh();
                }

                // Earn points based on actual paid amount
                $this->loyaltyService->applyEarnedPoints($customer, $salesOrder, $loyaltySettings);

                // Update total purchase and check membership threshold
                $this->loyaltyService->updateMembershipStatus($customer, $paidTotal, $loyaltySettings);
            }

            return [
                'sales_order' => $salesOrder->load(['items.sku.product', 'branch', 'warehouse', 'customer.user', 'cashier', 'payments', 'latestPayment']),
            ];
        });
    }

    private function resolveSaleType(?string $saleType): string
    {
        $normalized = trim((string) $saleType);

        if ($normalized === '') {
            return 'Sale';
        }

        $allowedTypes = ['Sale', 'Credit Sale', 'Quotation', 'Draft', 'Suspend'];

        if (!in_array($normalized, $allowedTypes, true)) {
            throw new RuntimeException('Invalid sale type selected.');
        }

        return $normalized;
    }

    private function getSaleModeConfig(string $saleType): array
    {
        return match ($saleType) {
            'Quotation', 'Draft', 'Suspend' => [
                'deduct_stock' => false,
                'sales_order_status' => 'draft',
                'fulfillment_status' => 'unfulfilled',
            ],
            default => [
                'deduct_stock' => true,
                'sales_order_status' => 'completed',
                'fulfillment_status' => 'fulfilled',
            ],
        };
    }

    private function getCartItemUnitDiscount($cartData): float
    {
        return (float) ($cartData->attributes->discount_amount ?? $cartData->attributes->discount ?? 0);
    }

    private function getCartItemDiscount($cartData): float
    {
        return $this->getCartItemUnitDiscount($cartData);
    }

    private function calculateLineTotal(float $unitPrice, int $quantity, float $discountAmount = 0): float
    {
        return max(0, ($unitPrice * $quantity) - $discountAmount);
    }

    private function generateSalesOrderNumber(?Branch $branch): string
    {
        $sequence = SalesOrder::query()
            ->when($branch?->id, fn ($query) => $query->where('branch_id', $branch->id))
            ->count() + 1;
        $prefix = $this->sanitizeBranchPrefix($branch?->code ?: $branch?->invoice_prefix ?: 'BR');

        return $prefix . '-SO-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function generateSalesInvoiceNumber(string $saleType, ?Branch $branch): ?string
    {
        if (in_array($saleType, ['Quotation', 'Draft', 'Suspend'], true)) {
            return null;
        }

        $sequence = SalesOrder::query()
            ->when($branch?->id, fn ($query) => $query->where('branch_id', $branch->id))
            ->whereNotNull('invoice_no')
            ->count() + 1;
        $prefix = $this->sanitizeBranchPrefix($branch?->invoice_prefix ?: $branch?->code ?: 'INV');

        return $prefix . '-INV-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function buildInternalNote(string $saleType, string $paymentMethod, ?string $shipmentZoneName): string
    {
        $notes = [
            'Sale Type: ' . $saleType,
            'Payment Method: ' . $paymentMethod,
        ];

        if (!empty($shipmentZoneName)) {
            $notes[] = 'Shipment Zone: ' . $shipmentZoneName;
        }

        return implode(' | ', $notes);
    }

    private function prepareCheckoutPayments(array $data, string $saleType, float $grandTotal): array
    {
        if (in_array($saleType, ['Quotation', 'Draft', 'Suspend'], true)) {
            return [
                'entries' => [],
                'paid_total' => 0.0,
                'summary_label' => 'N/A',
            ];
        }

        $entries = collect($data['payments'] ?? [])
            ->map(function ($payment) {
                $amount = round((float) ($payment['amount'] ?? 0), 2);

                return [
                    'payment_method' => Payment::normalizeMethod((string) ($payment['method'] ?? '')),
                    'amount' => $amount,
                    'note' => trim((string) ($payment['note'] ?? '')),
                ];
            })
            ->filter(fn (array $payment) => $payment['amount'] > 0)
            ->values();

        if ($entries->isEmpty() && !empty($data['payment_method']) && $grandTotal > 0) {
            $entries = collect([[
                'payment_method' => Payment::normalizeMethod((string) $data['payment_method']),
                'amount' => round($grandTotal, 2),
                'note' => '',
            ]]);
        }

        $paidTotal = round((float) $entries->sum('amount'), 2);

        if ($paidTotal - $grandTotal > 0.00001) {
            throw new RuntimeException('Collected payment cannot exceed the invoice total.');
        }

        return [
            'entries' => $entries->all(),
            'paid_total' => $paidTotal,
            'summary_label' => $this->buildPaymentSummaryLabel($entries->pluck('payment_method')->all()),
        ];
    }

    private function resolveSalesPaymentStatus(string $saleType, float $grandTotal, float $paidTotal): string
    {
        if (in_array($saleType, ['Quotation', 'Draft', 'Suspend'], true)) {
            return 'unpaid';
        }

        if ($paidTotal <= 0) {
            return 'unpaid';
        }

        if ($paidTotal + 0.00001 < $grandTotal) {
            return 'partial';
        }

        return 'paid';
    }

    private function buildPaymentSummaryLabel(array $methods): string
    {
        $labels = collect($methods)
            ->filter(fn ($method) => trim((string) $method) !== '')
            ->map(fn ($method) => Payment::labelForMethod((string) $method))
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return 'N/A';
        }

        if ($labels->count() === 1) {
            return $labels->first();
        }

        return 'Mixed (' . $labels->implode(' + ') . ')';
    }

    private function buildCheckoutPaymentNote(string $saleType, int $position, int $totalEntries): string
    {
        if ($totalEntries <= 1) {
            return $saleType . ' payment collected from POS checkout.';
        }

        return sprintf('%s POS split payment %d of %d.', $saleType, $position, $totalEntries);
    }

    private function sanitizeBranchPrefix(string $value): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $value));

        return $normalized !== '' ? $normalized : 'BR';
    }
}
