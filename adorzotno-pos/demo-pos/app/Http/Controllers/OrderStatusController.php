<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PosInventoryService;

class OrderStatusController extends Controller
{
    public function __construct(private PosInventoryService $posInventoryService)
    {
    }

    public function show(Request $request)
    {
        $order = SalesOrder::query()
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($request->orderId);
        return view('order.orderStatusModal', compact('order'));
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'orderId' => 'required|exists:sales_orders,id',
            'orderStatus' => 'required|in:draft,pending,confirmed,processing,packed,shipped,delivered,completed,cancelled,returned',
        ]);
        
        DB::beginTransaction();

        $order = SalesOrder::query()
            ->with('items')
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($request->orderId);
        $newStatus = $validated['orderStatus'];

        if (in_array($newStatus, ['cancelled', 'returned'], true)) {
            $this->posInventoryService->restoreStockForSalesOrder(
                $order,
                $newStatus === 'returned' ? 'sale_return_in' : 'sale_cancel_in',
                auth()->id(),
                'Stock restored after order status changed to ' . $newStatus . '.'
            );
        }

        $order->update([
            'status' => $newStatus,
            'fulfillment_status' => match ($newStatus) {
                'returned' => 'returned',
                'cancelled' => 'unfulfilled',
                'completed', 'delivered' => 'fulfilled',
                default => $order->fulfillment_status,
            },
        ]);
     
        DB::commit();

        return response()->json([
            'message' => 'Order Status saved successfully.'
        ]);
    }
}
