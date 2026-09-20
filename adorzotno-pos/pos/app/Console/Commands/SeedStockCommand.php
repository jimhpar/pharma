<?php

namespace App\Console\Commands;

use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\Sku;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedStockCommand extends Command
{
    protected $signature = 'inventory:seed-stock {--count=4000 : Number of active products to seed stock for}';
    protected $description = 'Seed realistic central and branch stock for active products';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        if ($count <= 0) {
            $count = 4000;
        }

        $this->info("Starting stock seeding for {$count} products...");

        $activeWarehouses = Warehouse::query()
            ->where('is_active', 1)
            ->whereIn('id', [1, 2, 5])
            ->get();

        if ($activeWarehouses->isEmpty()) {
            $this->error('No active warehouses found (expected warehouse IDs 1, 2, 5).');
            return 1;
        }

        $this->info("Active warehouses found: " . $activeWarehouses->pluck('name')->implode(', '));

        // Get random 4000 active SKUs
        $skus = Sku::query()
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->where('products.status', 'active')
            ->where('product_skus.status', 'active')
            ->select('product_skus.id', 'product_skus.retail_price', 'product_skus.cost_price')
            ->inRandomOrder()
            ->limit($count)
            ->get();

        $totalSkus = $skus->count();
        $this->info("Selected {$totalSkus} SKUs to seed stock for.");

        $chunkSize = 250;
        $chunks = $skus->chunk($chunkSize);
        $totalProcessed = 0;
        $totalBatches = 0;

        $bar = $this->output->createProgressBar($totalSkus);
        $bar->start();

        foreach ($chunks as $chunk) {
            DB::beginTransaction();
            try {
                $batchesToInsert = [];
                $stockBalancesToInsert = [];
                $transactionsToInsert = [];
                $now = Carbon::now();

                foreach ($chunk as $sku) {
                    // Decide how many warehouses get stock:
                    // 40% get all 3, 35% get 2, 25% get 1
                    $distRoll = rand(1, 100);
                    if ($distRoll <= 40) {
                        $selectedWhs = $activeWarehouses;
                    } elseif ($distRoll <= 75) {
                        $selectedWhs = $activeWarehouses->random(2);
                    } else {
                        $selectedWhs = $activeWarehouses->random(1);
                    }

                    $costPrice = (float) $sku->cost_price;
                    $retailPrice = (float) $sku->retail_price;
                    if ($costPrice <= 0 && $retailPrice > 0) {
                        $costPrice = round($retailPrice * (rand(68, 80) / 100), 2);
                    } elseif ($costPrice <= 0) {
                        $costPrice = (float) rand(40, 450);
                    }

                    if ($retailPrice <= 0) {
                        $retailPrice = round($costPrice * 1.25, 2);
                    }

                    foreach ($selectedWhs as $wh) {
                        $qty = rand(15, 120);

                        // Mfg date: between 2025-01-01 and 2026-06-01
                        $mfgDaysAgo = rand(100, 600);
                        $mfgDate = Carbon::now()->subDays($mfgDaysAgo)->format('Y-m-d');

                        // Expiry date: between 2027-06-01 and 2029-12-31
                        $expDaysFuture = rand(400, 1200);
                        $expDate = Carbon::now()->addDays($expDaysFuture)->format('Y-m-d');

                        $batchNo = 'BAT-' . $now->format('ymd') . '-' . sprintf('%04d', rand(1000, 9999)) . '-' . $sku->id;

                        // Insert batch and get ID
                        $batchId = DB::table('inventory_batches')->insertGetId([
                            'sku_id' => $sku->id,
                            'supplier_id' => null,
                            'warehouse_id' => $wh->id,
                            'batch_no' => $batchNo,
                            'purchase_price' => $costPrice,
                            'sale_price' => $retailPrice,
                            'received_quantity' => $qty,
                            'available_quantity' => $qty,
                            'manufacture_date' => $mfgDate,
                            'expiry_date' => $expDate,
                            'received_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $stockBalancesToInsert[] = [
                            'branch_id' => $wh->branch_id,
                            'warehouse_id' => $wh->id,
                            'sku_id' => $sku->id,
                            'batch_id' => $batchId,
                            'available_quantity' => $qty,
                            'reserved_quantity' => 0,
                            'reorder_level' => rand(5, 15),
                            'updated_at' => $now,
                        ];

                        $transactionsToInsert[] = [
                            'branch_id' => $wh->branch_id,
                            'warehouse_id' => $wh->id,
                            'sku_id' => $sku->id,
                            'batch_id' => $batchId,
                            'reference_type' => 'opening_stock_seeder',
                            'reference_id' => $batchId,
                            'movement_type' => 'stock_in',
                            'quantity' => $qty,
                            'balance_after' => $qty,
                            'unit_cost' => $costPrice,
                            'remarks' => "Stock seeding of {$qty} units for {$wh->name}",
                            'occurred_at' => $now,
                            'created_by' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $totalBatches++;
                    }

                    $totalProcessed++;
                    $bar->advance();
                }

                if (!empty($stockBalancesToInsert)) {
                    DB::table('stock_balances')->insert($stockBalancesToInsert);
                }

                if (!empty($transactionsToInsert)) {
                    DB::table('inventory_transactions')->insert($transactionsToInsert);
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("\nError in chunk: " . $e->getMessage());
                return 1;
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Stock seeding successfully completed!");
        $this->info("Total SKUs seeded: {$totalProcessed}");
        $this->info("Total Inventory Batches & Stock Balances created: {$totalBatches}");

        return 0;
    }
}
