<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierReturn;
use App\Support\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function show()
    {
        $suppliers = Supplier::query()->orderBy('id', 'desc')->get();

        return view('supplier.index', compact('suppliers'));
    }

    public function list(Request $request)
    {
        $suppliers = Supplier::query()
            ->with([
                'purchaseOrders:id,supplier_id,grand_total,paid_total,due_total,purchase_no,purchase_date,status',
                'supplierLedgers:id,supplier_id,entry_date,reference_type,reference_id,debit,credit,balance_after,remarks,created_at',
                'payments:id,supplier_id,amount,payment_direction,payment_purpose,payment_date',
            ])
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('id', 'desc');

        return DataTables()->of($suppliers)
            ->addColumn('credit_limit_display', fn (Supplier $supplier) => $supplier->credit_limit !== null
                ? Currency::format($supplier->credit_limit)
                : 'No limit')
            ->addColumn('current_due_display', fn (Supplier $supplier) => Currency::format($this->calculateOutstandingDue($supplier)))
            ->addColumn('aging_summary', fn (Supplier $supplier) => $this->formatAgingSummary($this->buildAgingBuckets($supplier)))
            ->addColumn('status_badge', function (Supplier $supplier) {
                if ($supplier->status === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);

        $supplier = Supplier::query()->create($validated);
        SupplierLedger::syncOpeningBalance($supplier);

        return redirect()->route('supplier.show')->with('success', 'Supplier created successfully.');
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('supplier.edit', compact('supplier'));
    }

    public function statement(int $supplierId)
    {
        $supplier = Supplier::query()
            ->with([
                'purchaseOrders' => fn ($query) => $query->orderByDesc('purchase_date')->orderByDesc('id'),
                'supplierLedgers' => fn ($query) => $query->orderBy('entry_date')->orderBy('id'),
                'payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('id'),
                'supplierReturns' => fn ($query) => $query
                    ->with('items:id,supplier_return_id,quantity,unit_cost')
                    ->orderByDesc('return_date')
                    ->orderByDesc('id'),
            ])
            ->findOrFail($supplierId);

        $agingBuckets = $this->buildAgingBuckets($supplier);
        $outstandingDue = $this->calculateOutstandingDue($supplier);
        $purchaseGrandTotal = (float) $supplier->purchaseOrders->sum('grand_total');
        $purchasePaidTotal = (float) $supplier->purchaseOrders->sum('paid_total');
        $purchaseDueTotal = (float) $supplier->purchaseOrders->sum('due_total');
        $supplierPayments = $supplier->payments
            ->where('payment_direction', 'out')
            ->values();
        $directPaymentTotal = (float) $supplierPayments->sum('amount');
        $supplierReturnTotal = (float) $supplier->supplierReturns
            ->sum(fn (SupplierReturn $supplierReturn) => $this->calculateSupplierReturnValue($supplierReturn));
        $ledgerEntries = $this->buildLedgerEntries($supplier);
        $availableCredit = $supplier->credit_limit !== null
            ? max(0, (float) $supplier->credit_limit - $outstandingDue)
            : null;
        $lastActivityAt = collect()
            ->merge($supplier->purchaseOrders->pluck('purchase_date'))
            ->merge($supplierPayments->pluck('payment_date'))
            ->merge($supplier->supplierReturns->pluck('return_date'))
            ->merge($supplier->supplierLedgers->pluck('entry_date'))
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sortDesc()
            ->first();

        return view('supplier.statement', compact(
            'supplier',
            'agingBuckets',
            'outstandingDue',
            'purchaseGrandTotal',
            'purchasePaidTotal',
            'purchaseDueTotal',
            'supplierPayments',
            'directPaymentTotal',
            'supplierReturnTotal',
            'ledgerEntries',
            'availableCredit',
            'lastActivityAt',
        ));
    }

    public function update(Request $request, $supplierId)
    {
        $validated = $this->validateSupplier($request, (int) $supplierId);

        $supplier = Supplier::query()->findOrFail($supplierId);
        $supplier->update($validated);
        SupplierLedger::syncOpeningBalance($supplier->fresh());

        Session::flash('success', 'Supplier updated successfully.');

        return redirect()->route('supplier.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $supplier = Supplier::query()->where('id', $request->id)->first();
        if (!empty($supplier)) {
            $supplier->delete();
        }

        return response()->json(['success' => 'Supplier deleted successfully.']);
    }

    private function validateSupplier(Request $request, ?int $supplierId = null): array
    {
        $validated = $request->validate([
            'supplier_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('suppliers', 'supplier_code')->ignore($supplierId),
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplierId),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $openingBalance = (float) ($validated['opening_balance'] ?? 0);
        $currentDue = $openingBalance;

        if ($supplierId !== null) {
            $existingSupplier = Supplier::query()->find($supplierId);

            if ($existingSupplier) {
                $currentDue = max(
                    0,
                    ((float) $existingSupplier->current_due - (float) $existingSupplier->opening_balance) + $openingBalance
                );
            }
        }

        return [
            'supplier_code' => $validated['supplier_code'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'opening_balance' => $openingBalance,
            'current_due' => $currentDue,
            'credit_limit' => $validated['credit_limit'] ?? null,
            'status' => $validated['status'],
        ];
    }

    private function calculateOutstandingDue(Supplier $supplier): float
    {
        $ledgerEntries = $this->buildLedgerEntries($supplier);

        if ($ledgerEntries->isNotEmpty()) {
            return round((float) ($ledgerEntries->last()['balance'] ?? 0), 2);
        }

        return round((float) $supplier->current_due + (float) $supplier->purchaseOrders->sum('due_total'), 2);
    }

    private function buildAgingBuckets(Supplier $supplier): array
    {
        $buckets = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
        ];

        foreach ($supplier->purchaseOrders as $purchaseOrder) {
            $dueAmount = (float) $purchaseOrder->due_total;

            if ($dueAmount <= 0) {
                continue;
            }

            $ageInDays = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->startOfDay()->diffInDays(now()->startOfDay());

            if ($ageInDays <= 0) {
                $buckets['current'] += $dueAmount;
            } elseif ($ageInDays <= 30) {
                $buckets['1_30'] += $dueAmount;
            } elseif ($ageInDays <= 60) {
                $buckets['31_60'] += $dueAmount;
            } elseif ($ageInDays <= 90) {
                $buckets['61_90'] += $dueAmount;
            } else {
                $buckets['90_plus'] += $dueAmount;
            }
        }

        return array_map(fn ($amount) => round((float) $amount, 2), $buckets);
    }

    private function formatAgingSummary(array $agingBuckets): string
    {
        $labels = [
            'current' => 'Current',
            '1_30' => '1-30',
            '31_60' => '31-60',
            '61_90' => '61-90',
            '90_plus' => '90+',
        ];

        return collect($labels)
            ->map(function (string $label, string $key) use ($agingBuckets) {
                return $label . ': ' . Currency::format($agingBuckets[$key] ?? 0);
            })
            ->implode(' | ');
    }

    private function buildLedgerEntries(Supplier $supplier): BaseCollection
    {
        $entries = collect();
        $ledgerPurchaseIds = collect();
        $hasOpeningLedger = false;
        $purchaseReferenceMap = $supplier->purchaseOrders
            ->keyBy('id')
            ->map(fn ($purchaseOrder) => $purchaseOrder->purchase_no);

        if ($supplier->relationLoaded('supplierLedgers') && $supplier->supplierLedgers->isNotEmpty()) {
            foreach ($supplier->supplierLedgers as $entry) {
                if ($entry->reference_type === 'purchase_order') {
                    $ledgerPurchaseIds->push((int) $entry->reference_id);
                }

                if ($entry->reference_type === 'opening_balance') {
                    $hasOpeningLedger = true;
                }

                $entries->push([
                    'date' => $entry->entry_date ?? $entry->created_at ?? now(),
                    'reference' => $this->formatLedgerReference($entry, $purchaseReferenceMap),
                    'type' => $this->formatLedgerType($entry->reference_type),
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                    'note' => $entry->remarks ?: 'Supplier ledger entry',
                ]);
            }
        }

        if (!$hasOpeningLedger && (float) $supplier->opening_balance > 0) {
            $entries->push([
                'date' => $supplier->created_at ?? now(),
                'reference' => 'Opening Balance',
                'type' => 'Opening Due',
                'debit' => (float) $supplier->opening_balance,
                'credit' => 0.0,
                'note' => 'Opening supplier balance carried into the ledger.',
            ]);
        }

        foreach ($supplier->purchaseOrders as $purchaseOrder) {
            if ($ledgerPurchaseIds->contains((int) $purchaseOrder->id)) {
                continue;
            }

            $entries->push([
                'date' => $purchaseOrder->purchase_date ?? $purchaseOrder->created_at,
                'reference' => $purchaseOrder->purchase_no,
                'type' => 'Purchase Order',
                'debit' => (float) $purchaseOrder->grand_total,
                'credit' => 0.0,
                'note' => 'Purchase booked for supplier.',
            ]);

            if ((float) $purchaseOrder->paid_total > 0) {
                $entries->push([
                    'date' => $purchaseOrder->purchase_date ?? $purchaseOrder->created_at,
                    'reference' => $purchaseOrder->purchase_no,
                    'type' => 'Purchase Payment',
                    'debit' => 0.0,
                    'credit' => (float) $purchaseOrder->paid_total,
                    'note' => 'Payment recorded on purchase order.',
                ]);
            }
        }

        foreach ($supplier->payments as $payment) {
            if ($payment->payment_direction !== 'out') {
                continue;
            }

            $entries->push([
                'date' => $payment->payment_date ?? $payment->created_at,
                'reference' => $payment->reference_no ?: ('PAY-' . $payment->id),
                'type' => 'Supplier Payment',
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'note' => $payment->note ?: 'Direct supplier payment recorded.',
            ]);
        }

        $runningBalance = 0.0;

        return $entries
            ->sortBy(fn (array $entry) => Carbon::parse($entry['date'] ?? now())->timestamp)
            ->values()
            ->map(function (array $entry) use (&$runningBalance) {
                $runningBalance += (float) $entry['debit'];
                $runningBalance -= (float) $entry['credit'];
                $entry['balance'] = round($runningBalance, 2);

                return $entry;
            });
    }

    private function formatLedgerReference(SupplierLedger $entry, BaseCollection $purchaseReferenceMap): string
    {
        if ($entry->reference_type === 'purchase_order') {
            return $purchaseReferenceMap->get((int) $entry->reference_id, 'PO #' . $entry->reference_id);
        }

        if ($entry->reference_type === 'opening_balance') {
            return 'Opening Balance';
        }

        return strtoupper((string) $entry->reference_type) . ' #' . $entry->reference_id;
    }

    private function formatLedgerType(?string $referenceType): string
    {
        return match ((string) $referenceType) {
            'opening_balance' => 'Opening Due',
            'purchase_order' => 'Purchase Order',
            default => ucwords(str_replace('_', ' ', (string) $referenceType)),
        };
    }

    private function calculateSupplierReturnValue(SupplierReturn $supplierReturn): float
    {
        return round((float) $supplierReturn->items->sum(
            fn ($item) => (float) $item->unit_cost * (float) $item->quantity
        ), 2);
    }
}
