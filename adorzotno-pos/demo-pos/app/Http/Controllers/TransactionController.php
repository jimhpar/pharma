<?php

namespace App\Http\Controllers;

use App\Models\CustomerLedger;
use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function show(Request $request)
    {
        $order = SalesOrder::with(['customer', 'payments'])
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($request->orderId);
        return view('order.payment-modal', compact('order'));
    }

    public function savePayment(Request $request)
    {
        // dd($request->all());
        $validated = $this->validate($request, [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'note'=>'nullable',            
        ]);
        
        DB::beginTransaction();
        
        $order = SalesOrder::query()
            ->when($this->currentBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->findOrFail($request->orderId);
        $currentTransactionAmount = (float) Payment::query()->where('sales_order_id', $order->id)->sum('amount');
        $newTotalAmount = $currentTransactionAmount + $validated['amount'];

        if ($newTotalAmount > (float) $request->orderTotal) {
            DB::rollback();
            return response()->json([
                'message' => 'Payment exceeds order total amount.'
            ], 422);
        }

        $payment = Payment::query()->create([
            'sales_order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'branch_id' => $order->branch_id,
            'payment_direction' => 'in',
            'payment_purpose' => 'sale_due',
            'payment_method' => Payment::normalizeMethod($validated['payment_method']),
            'amount' => $validated['amount'],
            'payment_date' => now(),
            'note' => $validated['note'],
            'received_by' => auth()->id(),
        ]);

        $grandTotal = (float) $order->grand_total;
        $dueAmount = max(0, $grandTotal - $newTotalAmount);
        $paymentStatus = $newTotalAmount <= 0
            ? 'unpaid'
            : ($newTotalAmount + 0.00001 < $grandTotal ? 'partial' : 'paid');

        $order->update([
            'paid_total' => $newTotalAmount,
            'due_total' => $dueAmount,
            'payment_status' => $paymentStatus,
        ]);

        CustomerLedger::syncCollectionEntry($payment);

        DB::commit();

        return response()->json([
            'message' => 'Payment saved successfully.'
        ]);
    }
}
