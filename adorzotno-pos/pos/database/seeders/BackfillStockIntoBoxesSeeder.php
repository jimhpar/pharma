<?php

namespace Database\Seeders;

use App\Models\InventoryBox;
use App\Models\InventoryCarton;
use App\Models\StockBalance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillStockIntoBoxesSeeder extends Seeder
{
    public function run(): void
    {
        $createdCartons = 0;
        $createdBoxes = 0;
        $coveredUnits = 0;

        StockBalance::query()
            ->where('available_quantity', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($balances) use (&$createdCartons, &$createdBoxes, &$coveredUnits) {
                foreach ($balances as $balance) {
                    DB::transaction(function () use ($balance, &$createdCartons, &$createdBoxes, &$coveredUnits) {
                        $boxedUnits = InventoryBox::query()
                            ->where('sku_id', $balance->sku_id)
                            ->whereHas('carton', function ($query) use ($balance) {
                                $query->where('warehouse_id', $balance->warehouse_id);

                                if ($balance->batch_id === null) {
                                    $query->whereNull('batch_id');
                                } else {
                                    $query->where('batch_id', $balance->batch_id);
                                }
                            })
                            ->sum('units_available');

                        $gap = max((int) $balance->available_quantity - (int) $boxedUnits, 0);

                        if ($gap <= 0) {
                            return;
                        }

                        $cartonCode = $this->uniqueCartonCode($balance->id);

                        $carton = InventoryCarton::query()->create([
                            'purchase_order_item_id' => null,
                            'sku_id' => $balance->sku_id,
                            'warehouse_id' => $balance->warehouse_id,
                            'batch_id' => $balance->batch_id,
                            'carton_code' => $cartonCode,
                            'boxes_per_carton' => 1,
                            'units_per_box' => $gap,
                            'total_units' => $gap,
                            'available_units' => $gap,
                            'received_at' => now(),
                            'notes' => 'Auto-backfilled from existing stock balance #' . $balance->id,
                        ]);

                        InventoryBox::query()->create([
                            'carton_id' => $carton->id,
                            'sku_id' => $balance->sku_id,
                            'box_code' => $cartonCode . '-B01',
                            'box_number' => 1,
                            'units_received' => $gap,
                            'units_available' => $gap,
                            'units_sold' => 0,
                            'status' => 'sealed',
                        ]);

                        $createdCartons++;
                        $createdBoxes++;
                        $coveredUnits += $gap;
                    });
                }
            });

        $this->command?->info(
            "Backfilled {$coveredUnits} units into {$createdBoxes} boxes across {$createdCartons} cartons."
        );
    }

    private function uniqueCartonCode(int $stockBalanceId): string
    {
        $base = 'BF-' . now()->format('Ymd') . '-SB' . $stockBalanceId;
        $code = $base;
        $suffix = 1;

        while (InventoryCarton::query()->where('carton_code', $code)->exists()) {
            $code = $base . '-' . $suffix++;
        }

        return $code;
    }
}
