<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Sku;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvailableStockController extends Controller
{
    public function show(Request $request)
    {
        $warehouses = Warehouse::query()
            ->with('branch:id,name')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Summary KPI figures
        $totalProducts = Product::query()->where('status', 'active')->count();

        $inStockSkusCount = StockBalance::query()
            ->select('sku_id')
            ->groupBy('sku_id')
            ->havingRaw('SUM(available_quantity - reserved_quantity) > 0')
            ->get()
            ->count();

        $totalStockUnits = (int) StockBalance::query()->sum('available_quantity');

        $outOfStockCount = max(0, $totalProducts - $inStockSkusCount);

        return view('available_stock.index', compact(
            'warehouses',
            'categories',
            'brands',
            'totalProducts',
            'inStockSkusCount',
            'outOfStockCount',
            'totalStockUnits'
        ));
    }

    public function list(Request $request)
    {
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) {
            $length = 25;
        }

        $search = trim((string) data_get($request->input('search'), 'value', ''));
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
        }

        $warehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $brandId = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $stockStatus = $request->input('stock_status');

        $activeWarehouses = Warehouse::query()
            ->with('branch:id,name')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();

        $query = DB::table('product_skus')
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('products.status', 'active')
            ->select([
                'product_skus.id as sku_id',
                'product_skus.sku_code',
                'product_skus.barcode',
                'product_skus.retail_price',
                'product_skus.cost_price',
                'products.id as product_id',
                'products.name as product_name',
                'products.thumbnail_image',
                'products.manufacturer_name',
                'categories.name as category_name',
                'brands.name as brand_name',
                DB::raw('(SELECT COALESCE(SUM(sb.available_quantity - sb.reserved_quantity), 0) FROM stock_balances sb WHERE sb.sku_id = product_skus.id) as central_stock'),
                DB::raw('(SELECT MIN(ib.expiry_date) FROM inventory_batches ib WHERE ib.sku_id = product_skus.id AND ib.available_quantity > 0 AND ib.expiry_date IS NOT NULL) as nearest_expiry'),
                DB::raw('(SELECT MAX(ib.manufacture_date) FROM inventory_batches ib WHERE ib.sku_id = product_skus.id AND ib.manufacture_date IS NOT NULL) as latest_manufacture'),
            ]);

        if ($search !== '') {
            $query->where(function ($sq) use ($search) {
                $sq->where('products.name', 'like', "%{$search}%")
                    ->orWhere('product_skus.sku_code', 'like', "%{$search}%")
                    ->orWhere('product_skus.barcode', 'like', "%{$search}%")
                    ->orWhere('product_skus.id', '=', $search);
            });
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        if ($brandId) {
            $query->where('products.brand_id', $brandId);
        }

        if ($warehouseId) {
            $query->whereExists(function ($sq) use ($warehouseId) {
                $sq->select(DB::raw(1))
                    ->from('stock_balances')
                    ->whereColumn('stock_balances.sku_id', 'product_skus.id')
                    ->where('stock_balances.warehouse_id', $warehouseId)
                    ->where('stock_balances.available_quantity', '>', 0);
            });
        }

        if ($stockStatus === 'in_stock') {
            $query->having('central_stock', '>', 0);
        } elseif ($stockStatus === 'out_of_stock') {
            $query->having('central_stock', '<=', 0);
        } elseif ($stockStatus === 'low_stock') {
            $query->having('central_stock', '>', 0)->having('central_stock', '<=', 10);
        }

        $totalRecords = DB::table('product_skus')
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->where('products.status', 'active')
            ->count();

        // Count filtered records
        $filteredRecords = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count();

        $rows = $query->orderByDesc('central_stock')
            ->orderBy('products.id')
            ->offset($start)
            ->limit($length)
            ->get();

        $skuIds = $rows->pluck('sku_id')->all();

        // Preload branch-wise balances for all loaded SKUs
        $branchBalances = [];
        if (!empty($skuIds)) {
            $balances = DB::table('stock_balances')
                ->whereIn('sku_id', $skuIds)
                ->select([
                    'sku_id',
                    'warehouse_id',
                    DB::raw('SUM(available_quantity - reserved_quantity) as branch_stock')
                ])
                ->groupBy('sku_id', 'warehouse_id')
                ->get();

            foreach ($balances as $bal) {
                $branchBalances[$bal->sku_id][$bal->warehouse_id] = (int) $bal->branch_stock;
            }
        }

        $data = [];
        foreach ($rows as $row) {
            $branches = [];
            foreach ($activeWarehouses as $wh) {
                $branches[] = [
                    'warehouse_id' => $wh->id,
                    'warehouse_name' => $wh->name,
                    'branch_name' => $wh->branch?->name ?? 'Branch',
                    'stock' => $branchBalances[$row->sku_id][$wh->id] ?? 0,
                ];
            }

            $manufacturer = $row->manufacturer_name ?: ($row->brand_name ?: 'N/A');

            $data[] = [
                'sku_id' => $row->sku_id,
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'thumbnail' => $row->thumbnail_image ? url($row->thumbnail_image) : null,
                'category_name' => $row->category_name ?? 'General',
                'brand_name' => $row->brand_name ?? 'N/A',
                'manufacturer' => $manufacturer,
                'sku_code' => $row->sku_code,
                'barcode' => $row->barcode ?? 'N/A',
                'retail_price' => number_format((float) $row->retail_price, 2),
                'central_stock' => (int) $row->central_stock,
                'nearest_expiry' => $row->nearest_expiry ? date('d M Y', strtotime($row->nearest_expiry)) : 'N/A',
                'latest_manufacture' => $row->latest_manufacture ? date('d M Y', strtotime($row->latest_manufacture)) : 'N/A',
                'branches' => $branches,
                'status' => (int) $row->central_stock > 0 ? 'in_stock' : 'out_of_stock',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function productDetails(int $skuId): JsonResponse
    {
        $sku = DB::table('product_skus')
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('product_skus.id', $skuId)
            ->select([
                'product_skus.id as sku_id',
                'product_skus.sku_code',
                'product_skus.barcode',
                'product_skus.retail_price',
                'product_skus.cost_price',
                'products.id as product_id',
                'products.name as product_name',
                'products.manufacturer_name',
                'products.thumbnail_image',
                'categories.name as category_name',
                'brands.name as brand_name',
            ])
            ->first();

        if (!$sku) {
            return response()->json(['success' => false, 'message' => 'Product SKU not found'], 404);
        }

        $activeWarehouses = Warehouse::query()
            ->with('branch:id,name')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();

        // Branch-wise stock
        $branchBalances = DB::table('stock_balances')
            ->where('sku_id', $skuId)
            ->select([
                'warehouse_id',
                DB::raw('SUM(available_quantity - reserved_quantity) as total_stock')
            ])
            ->groupBy('warehouse_id')
            ->pluck('total_stock', 'warehouse_id')
            ->all();

        $branchBreakdown = [];
        $centralStock = 0;
        foreach ($activeWarehouses as $wh) {
            $stock = (int) ($branchBalances[$wh->id] ?? 0);
            $centralStock += $stock;
            $branchBreakdown[] = [
                'warehouse_id' => $wh->id,
                'warehouse_name' => $wh->name,
                'branch_id' => $wh->branch_id,
                'branch_name' => $wh->branch?->name ?? 'Branch',
                'stock' => $stock,
            ];
        }

        // Batches
        $batches = DB::table('inventory_batches')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_batches.warehouse_id')
            ->leftJoin('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->where('inventory_batches.sku_id', $skuId)
            ->where('inventory_batches.available_quantity', '>', 0)
            ->select([
                'inventory_batches.id',
                'inventory_batches.batch_no',
                'inventory_batches.warehouse_id',
                'inventory_batches.available_quantity',
                'inventory_batches.received_quantity',
                'inventory_batches.purchase_price',
                'inventory_batches.sale_price',
                'inventory_batches.manufacture_date',
                'inventory_batches.expiry_date',
                'inventory_batches.received_at',
                'warehouses.name as warehouse_name',
                'branches.name as branch_name',
            ])
            ->orderByDesc('inventory_batches.id')
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'batch_no' => $b->batch_no,
                    'warehouse_id' => $b->warehouse_id,
                    'warehouse_name' => $b->warehouse_name,
                    'branch_name' => $b->branch_name ?? 'Branch',
                    'available_quantity' => (int) $b->available_quantity,
                    'received_quantity' => (int) $b->received_quantity,
                    'purchase_price' => $b->purchase_price ? (float) $b->purchase_price : 0,
                    'sale_price' => $b->sale_price ? (float) $b->sale_price : 0,
                    'manufacture_date' => $b->manufacture_date,
                    'manufacture_date_formatted' => $b->manufacture_date ? date('d M Y', strtotime($b->manufacture_date)) : 'N/A',
                    'expiry_date' => $b->expiry_date,
                    'expiry_date_formatted' => $b->expiry_date ? date('d M Y', strtotime($b->expiry_date)) : 'N/A',
                ];
            });

        return response()->json([
            'success' => true,
            'product' => [
                'sku_id' => $sku->sku_id,
                'product_id' => $sku->product_id,
                'name' => $sku->product_name,
                'sku_code' => $sku->sku_code,
                'barcode' => $sku->barcode ?? 'N/A',
                'manufacturer' => $sku->manufacturer_name ?: ($sku->brand_name ?: 'N/A'),
                'category' => $sku->category_name ?? 'General',
                'retail_price' => (float) $sku->retail_price,
                'cost_price' => (float) $sku->cost_price,
                'thumbnail' => $sku->thumbnail_image ? url($sku->thumbnail_image) : null,
                'central_stock' => $centralStock,
                'branches' => $branchBreakdown,
                'batches' => $batches,
            ]
        ]);
    }

    public function storeStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'batch_no' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $warehouse = Warehouse::query()->findOrFail($validated['warehouse_id']);
        $sku = Sku::query()->findOrFail($validated['sku_id']);
        $qty = (int) $validated['quantity'];
        $purchasePrice = $validated['purchase_price'] ?? $sku->cost_price ?? 0;
        $salePrice = $validated['sale_price'] ?? $sku->retail_price ?? 0;
        $userId = Auth::id() ?? 1;

        DB::beginTransaction();
        try {
            // 1. Create inventory batch
            $batch = InventoryBatch::create([
                'sku_id' => $sku->id,
                'warehouse_id' => $warehouse->id,
                'batch_no' => $validated['batch_no'],
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'received_quantity' => $qty,
                'available_quantity' => $qty,
                'manufacture_date' => $validated['manufacture_date'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'received_at' => now(),
            ]);

            // 2. Insert or update stock_balances row
            StockBalance::create([
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'sku_id' => $sku->id,
                'batch_id' => $batch->id,
                'available_quantity' => $qty,
                'reserved_quantity' => 0,
                'reorder_level' => 0,
                'updated_at' => now(),
            ]);

            // 3. Record inventory transaction
            InventoryTransaction::create([
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'sku_id' => $sku->id,
                'batch_id' => $batch->id,
                'reference_type' => 'available_stock_management',
                'reference_id' => $batch->id,
                'movement_type' => 'stock_in',
                'quantity' => $qty,
                'balance_after' => $qty,
                'unit_cost' => $purchasePrice,
                'remarks' => $validated['remarks'] ?? "Added stock of {$qty} units via Available Stock Management",
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully added {$qty} units of stock for {$sku->sku_code} in {$warehouse->name}.",
                'batch_id' => $batch->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error adding stock in AvailableStockController: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStock(Request $request, int $batchId): JsonResponse
    {
        $validated = $request->validate([
            'batch_no' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $batch = InventoryBatch::query()->findOrFail($batchId);
        $newQty = (int) $validated['quantity'];
        $oldQty = (int) $batch->available_quantity;
        $qtyDiff = $newQty - $oldQty;
        $userId = Auth::id() ?? 1;

        DB::beginTransaction();
        try {
            // Update batch
            $batch->batch_no = $validated['batch_no'];
            $batch->available_quantity = $newQty;
            if ($newQty > $batch->received_quantity) {
                $batch->received_quantity = $newQty;
            }
            if (isset($validated['purchase_price'])) {
                $batch->purchase_price = $validated['purchase_price'];
            }
            if (isset($validated['sale_price'])) {
                $batch->sale_price = $validated['sale_price'];
            }
            $batch->manufacture_date = $validated['manufacture_date'] ?? null;
            $batch->expiry_date = $validated['expiry_date'] ?? null;
            $batch->save();

            // Update corresponding stock balance
            $balance = StockBalance::where('batch_id', $batch->id)->first();
            if ($balance) {
                $balance->available_quantity = $newQty;
                $balance->updated_at = now();
                $balance->save();
            } else {
                // In case it was not linked, create it
                StockBalance::create([
                    'branch_id' => Warehouse::find($batch->warehouse_id)?->branch_id ?? 1,
                    'warehouse_id' => $batch->warehouse_id,
                    'sku_id' => $batch->sku_id,
                    'batch_id' => $batch->id,
                    'available_quantity' => $newQty,
                    'reserved_quantity' => 0,
                    'reorder_level' => 0,
                    'updated_at' => now(),
                ]);
            }

            // Log inventory transaction
            InventoryTransaction::create([
                'branch_id' => Warehouse::find($batch->warehouse_id)?->branch_id ?? 1,
                'warehouse_id' => $batch->warehouse_id,
                'sku_id' => $batch->sku_id,
                'batch_id' => $batch->id,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $batch->id,
                'movement_type' => $qtyDiff >= 0 ? 'adjustment_add' : 'adjustment_subtract',
                'quantity' => abs($qtyDiff),
                'balance_after' => $newQty,
                'unit_cost' => $batch->purchase_price,
                'remarks' => $validated['remarks'] ?? "Adjusted stock quantity from {$oldQty} to {$newQty}",
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock batch updated successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteStock(Request $request, int $batchId): JsonResponse
    {
        $batch = InventoryBatch::query()->findOrFail($batchId);
        $qty = (int) $batch->available_quantity;
        $userId = Auth::id() ?? 1;

        DB::beginTransaction();
        try {
            // Delete stock_balances row
            StockBalance::where('batch_id', $batch->id)->delete();

            // Set available quantity in batch to 0
            $batch->available_quantity = 0;
            $batch->save();

            // Log removal transaction
            InventoryTransaction::create([
                'branch_id' => Warehouse::find($batch->warehouse_id)?->branch_id ?? 1,
                'warehouse_id' => $batch->warehouse_id,
                'sku_id' => $batch->sku_id,
                'batch_id' => $batch->id,
                'reference_type' => 'stock_removal',
                'reference_id' => $batch->id,
                'movement_type' => 'stock_out',
                'quantity' => $qty,
                'balance_after' => 0,
                'unit_cost' => $batch->purchase_price,
                'remarks' => 'Deleted batch stock via Available Stock Management',
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch stock successfully removed.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleting stock batch: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove stock: ' . $e->getMessage(),
            ], 500);
        }
    }
}
