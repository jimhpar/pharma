<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Support\DateFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;

class StocksController extends Controller
{
    public function show()
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();

        $warehouses = Warehouse::query()
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'branch_id']);
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $brands = Brand::query()->orderBy('name')->get(['id', 'name']);

        return view('report.stock-report', compact('branches', 'warehouses', 'categories', 'brands'));
    }

    public function list(Request $request)
    {
        $stocks = $this->stockReportQuery($request);

        return DataTables()->of($stocks)
            ->addColumn('branch', fn ($row) => $row->branch_name ?? 'N/A')
            ->addColumn('warehouse', function ($row) {
                return trim(($row->warehouse_name ?? 'N/A') . (!empty($row->warehouse_code) ? ' (' . $row->warehouse_code . ')' : ''));
            })
            ->addColumn('product', function ($row) {
                $category = $row->category_name ?: null;
                $brand    = $row->brand_name ?: null;
                $variant  = $row->variant_name ?: null;
                $meta     = implode(' &bull; ', array_filter([$category, $brand]));

                $html  = '<div class="sp-name">' . e($row->product_name ?? 'N/A') . '</div>';
                if ($meta) {
                    $html .= '<div class="sp-meta">' . $meta . '</div>';
                }
                if ($variant) {
                    $html .= '<span class="sp-variant">' . e($variant) . '</span>';
                }
                return $html;
            })
            ->addColumn('sku', function ($row) {
                $barcode = $row->product_code;
                $html  = '<span class="sp-sku-code">' . e($row->sku_code ?? 'N/A') . '</span>';
                if ($barcode) {
                    $html .= '<div class="sp-barcode"><i class="bi bi-upc-scan"></i> ' . e($barcode) . '</div>';
                }
                return $html;
            })
            ->addColumn('available_stock', fn ($row) => (int) $row->available_stock)
            ->addColumn('reserved_stock', fn ($row) => (int) $row->reserved_stock)
            ->addColumn('stock_pack', function ($row) {
                $available    = (int) $row->available_stock;
                $unitsPerStrip = max(1, (int) ($row->units_per_strip ?? 1));
                $isMedicine   = str_contains(strtolower((string) $row->category_name), 'medicine')
                              || str_contains((string) $row->category_name, 'ঔষধ');

                if (!$isMedicine || $unitsPerStrip <= 1) {
                    return '<span class="text-muted">—</span>';
                }

                $strips = intdiv($available, $unitsPerStrip);
                $pieces = $available % $unitsPerStrip;

                return '<div class="sp-strip-num">' . $strips . ' strip</div>'
                    . '<div class="sp-strip-pcs">' . $pieces . ' pcs</div>';
            })
            ->addColumn('cost_price', fn ($row) => number_format((float) ($row->avg_batch_cost ?? $row->cost_price ?? 0), 2))
            ->addColumn('retail_price', fn ($row) => number_format((float) ($row->retail_price ?? 0), 2))
            ->addColumn('stock_value', function ($row) {
                $value = (int) $row->available_stock * (float) ($row->avg_batch_cost ?? $row->cost_price ?? 0);
                return number_format($value, 2);
            })
            ->addColumn('reorder_level', fn ($row) => (int) $row->reorder_level)
            ->editColumn('updated_at', fn ($row) => DateFormatter::humanDateTime($row->updated_at))
            ->addColumn('status_badge', fn ($row) => $this->stockStatusBadge((int) $row->available_stock, (int) $row->reorder_level))
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['product', 'sku', 'stock_pack', 'status_badge'])
            ->make(true);
    }

    public function summary(Request $request)
    {
        $base = $this->stockReportQuery($request);

        $rows = (clone $base)->get();

        $total    = $rows->count();
        $inStock  = $rows->filter(fn ($r) => (int) $r->available_stock > (int) $r->reorder_level || (int) $r->reorder_level === 0 && (int) $r->available_stock > 0)->count();
        $lowStock = $rows->filter(fn ($r) => (int) $r->reorder_level > 0 && (int) $r->available_stock > 0 && (int) $r->available_stock <= (int) $r->reorder_level)->count();
        $outStock = $rows->filter(fn ($r) => (int) $r->available_stock <= 0)->count();
        $totalValue = $rows->sum(fn ($r) => (int) $r->available_stock * (float) ($r->avg_batch_cost ?? $r->cost_price ?? 0));

        return response()->json([
            'total'       => $total,
            'in_stock'    => $inStock,
            'low_stock'   => $lowStock,
            'out_of_stock'=> $outStock,
            'total_value' => number_format($totalValue, 2),
        ]);
    }

    public function excel(Request $request)
    {
        $rows = $this->stockReportQuery($request)->get()->map(fn ($row) => [
            $row->branch_name ?? 'N/A',
            trim(($row->warehouse_name ?? 'N/A') . (!empty($row->warehouse_code) ? ' (' . $row->warehouse_code . ')' : '')),
            $row->sku_code ?? 'N/A',
            $row->product_code ?? 'N/A',
            $row->product_name ?? 'N/A',
            $row->variant_name ?: 'Single',
            $row->category_name ?? 'N/A',
            $row->brand_name ?? 'N/A',
            (int) $row->available_stock,
            (int) $row->reserved_stock,
            (int) $row->reorder_level,
            number_format((float) ($row->avg_batch_cost ?? $row->cost_price ?? 0), 2),
            number_format((float) ($row->retail_price ?? 0), 2),
            number_format((int) $row->available_stock * (float) ($row->avg_batch_cost ?? $row->cost_price ?? 0), 2),
            DateFormatter::dateTime($row->updated_at),
            $this->stockStatusText((int) $row->available_stock, (int) $row->reorder_level),
        ]);

        return $this->downloadCsv('product-stock-report-' . now()->format('Y-m-d-His'), [
            'Branch', 'Warehouse', 'SKU', 'Barcode', 'Product Name', 'Variant', 'Category', 'Brand',
            'Available Qty', 'Reserved Qty', 'Reorder Level', 'Cost Price', 'Retail Price', 'Stock Value', 'Updated At', 'Status',
        ], $rows);
    }

    private function stockReportQuery(Request $request)
    {
        $branchId = $this->resolveReportBranchId($request);

        $query = DB::table('stock_balances')
            ->join('product_skus', 'product_skus.id', '=', 'stock_balances.sku_id')
            ->join('products', 'products.id', '=', 'product_skus.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('branches', 'branches.id', '=', 'stock_balances.branch_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->select([
                'stock_balances.branch_id',
                'stock_balances.warehouse_id',
                'stock_balances.sku_id',
                'branches.name as branch_name',
                'warehouses.name as warehouse_name',
                'warehouses.code as warehouse_code',
                'product_skus.sku_code',
                'product_skus.barcode as product_code',
                'product_skus.variant_name',
                'product_skus.units_per_strip',
                'product_skus.cost_price',
                'product_skus.retail_price',
                DB::raw(
                    'COALESCE(' .
                    '(SELECT SUM(ib.available_quantity * ib.purchase_price) / NULLIF(SUM(ib.available_quantity), 0)' .
                    ' FROM inventory_batches ib' .
                    ' WHERE ib.sku_id = stock_balances.sku_id AND ib.warehouse_id = stock_balances.warehouse_id' .
                    ' AND ib.available_quantity > 0 AND ib.purchase_price > 0),' .
                    'product_skus.cost_price, 0) as avg_batch_cost'
                ),
                'products.name as product_name',
                'categories.name as category_name',
                'brands.name as brand_name',
                DB::raw('SUM(stock_balances.available_quantity) as available_stock'),
                DB::raw('SUM(stock_balances.reserved_quantity) as reserved_stock'),
                DB::raw('MAX(stock_balances.reorder_level) as reorder_level'),
                DB::raw('MAX(stock_balances.updated_at) as updated_at'),
            ])
            ->when($branchId, fn ($query, $selectedBranchId) => $query->where('stock_balances.branch_id', $selectedBranchId))
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('stock_balances.warehouse_id', (int) $request->input('warehouse_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('products.category_id', (int) $request->input('category_id')))
            ->when($request->filled('brand_id'), fn ($query) => $query->where('products.brand_id', (int) $request->input('brand_id')))
            ->when($request->filled('search.value'), function ($query) use ($request) {
                $search = (string) data_get($request->input('search'), 'value');
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('products.name', 'like', '%' . $search . '%')
                        ->orWhere('product_skus.sku_code', 'like', '%' . $search . '%')
                        ->orWhere('product_skus.barcode', 'like', '%' . $search . '%')
                        ->orWhere('categories.name', 'like', '%' . $search . '%')
                        ->orWhere('brands.name', 'like', '%' . $search . '%');
                });
            })
            ->groupBy([
                'stock_balances.branch_id',
                'stock_balances.warehouse_id',
                'stock_balances.sku_id',
                'branches.name',
                'warehouses.name',
                'warehouses.code',
                'product_skus.sku_code',
                'product_skus.barcode',
                'product_skus.variant_name',
                'product_skus.units_per_strip',
                'product_skus.cost_price',
                'product_skus.retail_price',
                'products.name',
                'categories.name',
                'brands.name',
            ]);

        if ($request->filled('stock_status')) {
            match ($request->input('stock_status')) {
                'in_stock'     => $query->havingRaw('SUM(stock_balances.available_quantity) > 0'),
                'out_of_stock' => $query->havingRaw('SUM(stock_balances.available_quantity) <= 0'),
                'low_stock'    => $query->havingRaw('SUM(stock_balances.available_quantity) > 0 AND SUM(stock_balances.available_quantity) <= MAX(stock_balances.reorder_level) AND MAX(stock_balances.reorder_level) > 0'),
                default        => null,
            };
        }

        if ($request->filled('min_qty')) {
            $query->havingRaw('SUM(stock_balances.available_quantity) >= ?', [(int) $request->input('min_qty')]);
        }

        if ($request->filled('max_qty')) {
            $query->havingRaw('SUM(stock_balances.available_quantity) <= ?', [(int) $request->input('max_qty')]);
        }

        return $query->orderByDesc('updated_at');
    }

    private function stockStatusText(int $availableStock, int $reorderLevel): string
    {
        if ($availableStock <= 0) {
            return 'Out of Stock';
        }

        if ($reorderLevel > 0 && $availableStock <= $reorderLevel) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    private function stockStatusBadge(int $availableStock, int $reorderLevel): string
    {
        return match ($this->stockStatusText($availableStock, $reorderLevel)) {
            'Out of Stock' => '<span class="stock-status stock-status-out">Out</span>',
            'Low Stock' => '<span class="stock-status stock-status-low">Low</span>',
            default => '<span class="stock-status stock-status-in">In Stock</span>',
        };
    }
}
