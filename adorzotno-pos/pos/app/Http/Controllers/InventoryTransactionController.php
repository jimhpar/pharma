<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryTransaction;
use App\Models\SalesOrder;
use App\Support\DateFormatter;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionController extends Controller
{
    public function show()
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();
        $salesChannels = SalesOrder::channelOptions();

        return view('inventory_transaction.index', compact('branches', 'salesChannels'));
    }

    public function list(Request $request)
    {
        $transactions = $this->inventoryLedgerQuery($request);

        return DataTables()->of($transactions)
            ->addColumn('branch', fn (InventoryTransaction $transaction) => $transaction->branch?->name ?? 'N/A')
            ->addColumn('warehouse', fn (InventoryTransaction $transaction) => $transaction->warehouse?->name ?? 'N/A')
            ->addColumn('product_name', fn (InventoryTransaction $transaction) => $transaction->sku?->product?->name ?? 'N/A')
            ->addColumn('sku_code', fn (InventoryTransaction $transaction) => $transaction->sku?->sku_code ?? 'N/A')
            ->addColumn('batch_no', fn (InventoryTransaction $transaction) => $transaction->batch?->batch_no ?? 'N/A')
            ->addColumn('sales_channel', fn (InventoryTransaction $transaction) => $this->salesChannelLabelForTransaction($transaction))
            ->editColumn('occurred_at', fn (InventoryTransaction $transaction) => DateFormatter::dateTime($transaction->occurred_at))
            ->addColumn('quantity_display', function (InventoryTransaction $transaction) {
                $quantity = (int) $transaction->quantity;
                $class = $quantity >= 0 ? 'btn btn-success' : 'btn btn-danger';
                $prefix = $quantity >= 0 ? '+' : '';

                return '<label class="' . $class . '">' . $prefix . $quantity . '</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['quantity_display'])
            ->make(true);
    }

    public function excel(Request $request)
    {
        $rows = $this->inventoryLedgerQuery($request)->get()->map(fn (InventoryTransaction $transaction) => [
            DateFormatter::dateTime($transaction->occurred_at),
            $transaction->branch?->name ?? 'N/A',
            $transaction->warehouse?->name ?? 'N/A',
            $transaction->sku?->product?->name ?? 'N/A',
            $transaction->sku?->sku_code ?? 'N/A',
            $transaction->batch?->batch_no ?? 'N/A',
            $this->salesChannelLabelForTransaction($transaction),
            $transaction->movement_type,
            (int) $transaction->quantity,
            (int) $transaction->balance_after,
            $transaction->reference_type,
            $transaction->remarks,
        ]);

        return $this->downloadCsv('inventory-ledger-' . now()->format('Y-m-d-His'), [
            'Date', 'Branch', 'Warehouse', 'Product', 'SKU', 'Batch', 'Sales Channel', 'Movement', 'Quantity', 'Balance After', 'Reference', 'Remarks',
        ], $rows);
    }

    private function inventoryLedgerQuery(Request $request)
    {
        $branchId = $this->resolveReportBranchId($request);

        return InventoryTransaction::query()
            ->with(['branch', 'warehouse', 'sku.product', 'batch', 'salesOrder'])
            ->when($branchId, fn ($query, $selectedBranchId) => $query->where('branch_id', $selectedBranchId))
            ->when($request->filled('salesChannel'), function (Builder $query) use ($request) {
                $query->where('reference_type', 'sales_order')
                    ->whereExists(function ($salesQuery) use ($request) {
                        $salesQuery
                            ->selectRaw('1')
                            ->from('sales_orders')
                            ->whereColumn('sales_orders.id', 'inventory_transactions.reference_id')
                            ->where('sales_orders.sales_channel', $request->salesChannel);
                    });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    private function salesChannelLabelForTransaction(InventoryTransaction $transaction): string
    {
        if ($transaction->reference_type !== 'sales_order' || empty($transaction->reference_id)) {
            return 'N/A';
        }

        $channel = $transaction->salesOrder?->sales_channel;

        return $channel ? (SalesOrder::channelOptions()[$channel] ?? ucfirst((string) $channel)) : 'N/A';
    }
}
