<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\SupplierLedger;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkPurchaseOrderStockSeeder extends Seeder
{
    public function run(): void
    {
        $orderCount = (int) env('BULK_PO_COUNT', 60);
        $itemsPerOrder = (int) env('BULK_PO_ITEMS', 6);
        $prefix = 'PO-BULK-' . now()->format('YmdHis');

        $warehouses = DB::table('warehouses')
            ->select('id', 'branch_id')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->get();

        $suppliers = DB::table('suppliers')
            ->select('id')
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $skus = DB::table('product_skus')
            ->select('id', 'cost_price')
            ->where('track_stock', 1)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($warehouses->isEmpty() || $suppliers->isEmpty() || $skus->count() < $itemsPerOrder) {
            $this->command?->error('Need warehouses, active suppliers, and enough stockable active SKUs before seeding purchase orders.');

            return;
        }

        $userId = User::query()->orderBy('id')->value('id');
        if ($userId !== null) {
            Auth::loginUsingId($userId);
        }

        $inventoryService = app(InventoryService::class);

        for ($orderIndex = 1; $orderIndex <= $orderCount; $orderIndex++) {
            $warehouse = $warehouses[($orderIndex - 1) % $warehouses->count()];
            $supplierId = $suppliers[($orderIndex - 1) % $suppliers->count()];
            $purchaseDate = now()->subDays(random_int(0, 20))->subHours(random_int(0, 8));
            $selectedSkus = $this->selectSkus($skus, $itemsPerOrder, $orderIndex);

            $items = [];
            $subTotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;

            foreach ($selectedSkus as $itemIndex => $sku) {
                $cartonPlan = $this->buildCartonPlan($prefix, $orderIndex, $itemIndex);
                $quantity = collect($cartonPlan)->sum('subtotal');
                $unitCost = (float) $sku->cost_price > 0
                    ? (float) $sku->cost_price
                    : random_int(35, 220);
                $unitCost = round($unitCost * (random_int(94, 108) / 100), 2);
                $grossTotal = $quantity * $unitCost;
                $discount = round($grossTotal * random_int(0, 4) / 100, 2);
                $tax = round(($grossTotal - $discount) * random_int(0, 3) / 100, 2);
                $lineTotal = $grossTotal - $discount + $tax;

                $subTotal += $grossTotal;
                $discountTotal += $discount;
                $taxTotal += $tax;

                $items[] = [
                    'sku_id' => (int) $sku->id,
                    'quantity' => $quantity,
                    'received_quantity' => 0,
                    'returned_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                    'batch_no' => sprintf('%s-B%02d', $prefix, $orderIndex) . '-I' . ($itemIndex + 1),
                    'expiry_date' => now()->addMonths(random_int(8, 30))->toDateString(),
                    'carton_qty' => count($cartonPlan),
                    'boxes_per_carton' => $cartonPlan[0]['boxes'],
                    'units_per_box' => $cartonPlan[0]['units_per_box'],
                    'carton_plan' => $cartonPlan,
                ];
            }

            $otherChargeTotal = random_int(0, 1) ? random_int(50, 450) : 0;
            $grandTotal = round($subTotal - $discountTotal + $taxTotal + $otherChargeTotal, 2);
            $paidTotal = round($grandTotal * random_int(45, 100) / 100, 2);

            $purchaseOrder = PurchaseOrder::query()->create([
                'branch_id' => (int) $warehouse->branch_id,
                'warehouse_id' => (int) $warehouse->id,
                'supplier_id' => (int) $supplierId,
                'purchase_no' => sprintf('%s-%04d', $prefix, $orderIndex),
                'purchase_date' => $purchaseDate,
                'status' => PurchaseOrder::STATUS_ORDERED,
                'sub_total' => round($subTotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'other_charge_total' => $otherChargeTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidTotal,
                'due_total' => max(round($grandTotal - $paidTotal, 2), 0),
                'note' => 'Bulk seeded purchase order with received stock.',
                'created_by' => $userId,
                'created_at' => $purchaseDate,
                'updated_at' => $purchaseDate,
            ]);

            foreach ($items as $item) {
                $purchaseOrder->items()->create($item);
            }

            SupplierLedger::syncPurchaseOrderEntry($purchaseOrder->fresh());

            $purchaseOrder->load('items');
            $inventoryService->receivePurchaseOrder(
                $purchaseOrder,
                $purchaseOrder->items->map(fn ($item) => [
                    'item_id' => $item->id,
                    'received_quantity' => $item->quantity,
                    'batch_no' => $item->batch_no,
                    'expiry_date' => optional($item->expiry_date)->toDateString(),
                    'cartons' => collect($item->carton_plan ?? [])->map(fn (array $carton, int $cartonIndex) => [
                        'carton_code' => sprintf('%s-I%02d-C%02d', $purchaseOrder->purchase_no, $item->id, $cartonIndex + 1),
                        'boxes_per_carton' => $carton['boxes'],
                        'units_per_box' => $carton['units_per_box'],
                        'unit_cost' => $carton['unit_cost'],
                        'batch_no' => $carton['batch_no'],
                        'expiry_date' => $carton['expiry_date'],
                    ])->all(),
                ])->all()
            );
        }

        $this->command?->info("Created and fully received {$orderCount} purchase orders.");
    }

    private function selectSkus(Collection $skus, int $count, int $orderIndex): Collection
    {
        $start = (($orderIndex - 1) * $count) % $skus->count();

        return collect(range(0, $count - 1))
            ->map(fn (int $offset) => $skus[($start + $offset) % $skus->count()]);
    }

    private function buildCartonPlan(string $prefix, int $orderIndex, int $itemIndex): array
    {
        $cartonCount = random_int(1, 3);
        $plans = [];

        for ($cartonIndex = 1; $cartonIndex <= $cartonCount; $cartonIndex++) {
            $boxes = random_int(2, 5);
            $unitsPerBox = random_int(6, 12);

            $plans[] = [
                'name' => 'Carton ' . $cartonIndex,
                'boxes' => $boxes,
                'units_per_box' => $unitsPerBox,
                'subtotal' => $boxes * $unitsPerBox,
                'unit_cost' => null,
                'batch_no' => sprintf('%s-B%02d-I%02d-C%02d', $prefix, $orderIndex, $itemIndex + 1, $cartonIndex),
                'expiry_date' => now()->addMonths(random_int(8, 30))->toDateString(),
            ];
        }

        return $plans;
    }
}
