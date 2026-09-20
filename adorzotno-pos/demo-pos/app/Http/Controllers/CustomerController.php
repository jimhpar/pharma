<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerLedger;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function show()
    {
        $stats = [
            'total'     => Customer::count(),
            'active'    => Customer::where('status', 'active')->count(),
            'has_due'   => Customer::where('current_due', '>', 0)->count(),
            'total_due' => Customer::sum('current_due'),
        ];

        $groups = CustomerGroup::orderBy('name')->get();

        return view('customer.index', compact('stats', 'groups'));
    }

    public function list(Request $request)
    {
        $customers = Customer::query()
            ->with([
                'customerGroup:id,name',
                'salesOrders:id,customer_id,branch_id,order_no,order_date,grand_total,paid_total,due_total,status,payment_status',
                'customerLedgers:id,customer_id,entry_date,reference_type,reference_id,debit,credit,balance_after,remarks,created_at',
                'payments:id,customer_id,sales_order_id,branch_id,payment_direction,payment_purpose,amount,payment_date,reference_no,note,created_at',
            ])
            ->when($request->filled('search_name'), fn ($q) =>
                $q->where(fn ($sub) => $sub
                    ->where('name',          'like', '%'.$request->search_name.'%')
                    ->orWhere('phone',       'like', '%'.$request->search_name.'%')
                    ->orWhere('email',       'like', '%'.$request->search_name.'%')
                    ->orWhere('customer_code','like','%'.$request->search_name.'%')
                )
            )
            ->when($request->filled('group_filter'), fn ($q) =>
                $q->whereHas('customerGroup', fn ($g) => $g->where('name', $request->group_filter))
            )
            ->when($request->filled('due_filter'), function ($q) use ($request) {
                if ($request->due_filter === 'due') {
                    $q->where('current_due', '>', 0);
                } elseif ($request->due_filter === 'clear') {
                    $q->where('current_due', '<=', 0);
                }
            })
            ->when($request->filled('status_filter'), fn ($q) =>
                $q->where('status', strtolower($request->status_filter))
            )
            ->orderByDesc('id');

        return DataTables()->of($customers)
            ->addColumn('group_name',           fn (Customer $c) => $c->customerGroup?->name ?? 'Ungrouped')
            ->addColumn('credit_limit_display', fn (Customer $c) => $c->credit_limit !== null ? Currency::format($c->credit_limit) : 'No limit')
            ->addColumn('current_due_display',  fn (Customer $c) => Currency::format($this->calculateOutstandingDue($c)))
            ->addColumn('raw_due',              fn (Customer $c) => (float) $this->calculateOutstandingDue($c))
            ->addColumn('statement_url',        fn (Customer $c) => route('customer.statement', $c->id))
            ->addColumn('edit_url',             fn (Customer $c) => route('customer.edit', $c->id))
            ->addColumn('status_badge', fn (Customer $c) => $c->status === 'active'
                ? '<span style="background:#d1fae5;color:#065f46;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px">Active</span>'
                : '<span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px">Inactive</span>'
            )
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        $customerGroups = CustomerGroup::query()->orderBy('name')->get();
        $suggestedCustomerCode = $this->generateCustomerCode();

        return view('customer.create', compact('customerGroups', 'suggestedCustomerCode'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCustomer($request);

        $customer = Customer::query()->create($validated);
        CustomerLedger::syncOpeningBalance($customer);

        return redirect()->route('customer.show')->with('success', 'Customer created successfully.');
    }

    public function edit(int $id)
    {
        $customer = Customer::query()->findOrFail($id);
        $customerGroups = CustomerGroup::query()->orderBy('name')->get();

        return view('customer.edit', compact('customer', 'customerGroups'));
    }

    public function statement(int $customerId)
    {
        $customer = Customer::query()
            ->with([
                'customerGroup:id,name,discount_percent',
                'salesOrders' => fn ($query) => $query
                    ->with([
                        'returns:id,sales_order_id,return_no,return_date,refund_total,status',
                        'payments:id,sales_order_id,customer_id,amount,payment_direction,payment_purpose,payment_date,reference_no,note,created_at',
                    ])
                    ->orderByDesc('order_date')
                    ->orderByDesc('id'),
                'customerLedgers' => fn ($query) => $query->orderBy('entry_date')->orderBy('id'),
                'payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('id'),
                'salesReturns' => fn ($query) => $query
                    ->with('salesOrder:id,customer_id,order_no,invoice_no')
                    ->orderByDesc('return_date')
                    ->orderByDesc('id'),
                'prescriptions' => fn ($query) => $query->orderByDesc('created_at'),
            ])
            ->findOrFail($customerId);

        $agingBuckets = $this->buildAgingBuckets($customer);
        $outstandingDue = $this->calculateOutstandingDue($customer);
        $salesGrandTotal = (float) $customer->salesOrders->sum('grand_total');
        $salesPaidTotal = (float) $customer->salesOrders->sum('paid_total');
        $salesDueTotal = (float) $customer->salesOrders->sum('due_total');
        $collectionEntries = $customer->payments
            ->where('payment_direction', 'in')
            ->values();
        $collectionTotal = (float) $collectionEntries->sum('amount');
        $returnTotal = (float) $customer->salesReturns->sum('refund_total');
        $ledgerEntries = $this->buildLedgerEntries($customer);
        $availableCredit = $customer->credit_limit !== null
            ? max(0, (float) $customer->credit_limit - $outstandingDue)
            : null;
        $lastActivityAt = collect()
            ->merge($customer->salesOrders->pluck('order_date'))
            ->merge($collectionEntries->pluck('payment_date'))
            ->merge($customer->salesReturns->pluck('return_date'))
            ->merge($customer->customerLedgers->pluck('entry_date'))
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sortDesc()
            ->first();

        return view('customer.statement', compact(
            'customer',
            'agingBuckets',
            'outstandingDue',
            'salesGrandTotal',
            'salesPaidTotal',
            'salesDueTotal',
            'collectionEntries',
            'collectionTotal',
            'returnTotal',
            'ledgerEntries',
            'availableCredit',
            'lastActivityAt',
        ));
    }

    public function report()
    {
        $branches = $this->branchContext()->hasCrossBranchAccess(auth()->user())
            ? $this->branchContext()->accessibleBranches(auth()->user())
            : collect();
        $customers = Customer::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'customer_code']);
        $salesChannels = SalesOrder::channelOptions();

        return view('report.customer-sales-report', compact('branches', 'customers', 'salesChannels'));
    }

    public function reportList(Request $request)
    {
        $customers = $this->customerSalesReportCustomers($request);
        $reportRows = $this->customerSalesReportRows($customers);

        return DataTables()->of($reportRows)
            ->addColumn('action', function (array $row) {
                return '<a title="statement" class="btn btn-info btn-xs" href="' . route('customer.statement', $row['id']) . '"><i class="fa fa-file-text"></i></a>';
            })
            ->rawColumns(['action'])
            ->with([
                'customerCount' => $reportRows->count(),
                'salesTotalSum' => round((float) $customers->sum(fn (Customer $customer) => $customer->salesOrders->sum('grand_total')), 2),
                'paidTotalSum' => round((float) $customers->sum(fn (Customer $customer) => $customer->salesOrders->sum('paid_total')), 2),
                'dueTotalSum' => round((float) $customers->sum(fn (Customer $customer) => $customer->salesOrders->sum('due_total')), 2),
            ])
            ->make(true);
    }

    public function reportExcel(Request $request)
    {
        $rows = $this->customerSalesReportRows($this->customerSalesReportCustomers($request))
            ->map(fn (array $row) => [
                $row['customer_code'],
                $row['customer_name'],
                $row['group_name'],
                $row['sales_channels'],
                $row['last_order_date'],
                $row['order_count'],
                $row['sales_total_raw'],
                $row['paid_total_raw'],
                $row['due_total_raw'],
                $row['return_total_raw'],
            ]);

        return $this->downloadCsv('customer-sales-report-' . now()->format('Y-m-d-His'), [
            'Code', 'Customer', 'Group', 'Channel', 'Last Order', 'Orders', 'Sales', 'Collected', 'Due', 'Returns',
        ], $rows);
    }

    public function update(Request $request, int $customerId)
    {
        $validated = $this->validateCustomer($request, $customerId);

        $customer = Customer::query()->findOrFail($customerId);
        $customer->update($validated);
        CustomerLedger::syncOpeningBalance($customer->fresh());

        Session::flash('success', 'Customer updated successfully.');

        return redirect()->route('customer.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $customer = Customer::query()->find($request->id);

        if ($customer !== null) {
            $customer->delete();
        }

        return response()->json(['success' => 'Customer deleted successfully.']);
    }

    private function validateCustomer(Request $request, ?int $customerId = null): array
    {
        $validated = $request->validate([
            'customer_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'customer_code')->ignore($customerId),
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'note' => 'nullable|string|max:2000',
            'opening_balance' => 'nullable|numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'loyalty_points' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $openingBalance = (float) ($validated['opening_balance'] ?? 0);
        $currentDue = $openingBalance;

        if ($customerId !== null) {
            $existingCustomer = Customer::query()->find($customerId);

            if ($existingCustomer) {
                $currentDue = max(
                    0,
                    ((float) $existingCustomer->current_due - (float) $existingCustomer->opening_balance) + $openingBalance
                );
            }
        }

        return [
            'customer_code' => $validated['customer_code'] ?: $this->generateCustomerCode(),
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'customer_group_id' => $validated['customer_group_id'] ?? null,
            'billing_address' => $validated['billing_address'] ?? null,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'note' => $validated['note'] ?? null,
            'opening_balance' => $openingBalance,
            'credit_limit' => $validated['credit_limit'] ?? null,
            'current_due' => $currentDue,
            'loyalty_points' => (int) ($validated['loyalty_points'] ?? 0),
            'status' => $validated['status'],
        ];
    }

    private function calculateOutstandingDue(Customer $customer): float
    {
        $ledgerEntries = $this->buildLedgerEntries($customer);

        if ($ledgerEntries->isNotEmpty()) {
            return round((float) ($ledgerEntries->last()['balance'] ?? 0), 2);
        }

        return round((float) $customer->current_due + (float) $customer->salesOrders->sum('due_total'), 2);
    }

    private function buildAgingBuckets(Customer $customer): array
    {
        $buckets = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
        ];

        foreach ($customer->salesOrders as $salesOrder) {
            $dueAmount = (float) $salesOrder->due_total;

            if ($dueAmount <= 0) {
                continue;
            }

            $ageInDays = Carbon::parse($salesOrder->order_date ?? $salesOrder->created_at)->startOfDay()->diffInDays(now()->startOfDay());

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

    private function buildLedgerEntries(Customer $customer): BaseCollection
    {
        $entries = collect();
        $ledgerOrderIds = collect();
        $ledgerPaymentIds = collect();
        $ledgerReturnIds = collect();
        $hasOpeningLedger = false;
        $orderReferenceMap = $customer->salesOrders
            ->keyBy('id')
            ->map(fn ($salesOrder) => $salesOrder->order_no ?: ('SO-' . $salesOrder->id));

        if ($customer->relationLoaded('customerLedgers') && $customer->customerLedgers->isNotEmpty()) {
            foreach ($customer->customerLedgers as $entry) {
                if ($entry->reference_type === 'sales_order') {
                    $ledgerOrderIds->push((int) $entry->reference_id);
                }

                if ($entry->reference_type === 'payment') {
                    $ledgerPaymentIds->push((int) $entry->reference_id);
                }

                if ($entry->reference_type === 'sales_return') {
                    $ledgerReturnIds->push((int) $entry->reference_id);
                }

                if ($entry->reference_type === 'opening_balance') {
                    $hasOpeningLedger = true;
                }

                $entries->push([
                    'date' => $entry->entry_date ?? $entry->created_at ?? now(),
                    'reference' => $this->formatLedgerReference($entry, $orderReferenceMap),
                    'type' => $this->formatLedgerType($entry->reference_type),
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                    'note' => $entry->remarks ?: 'Customer ledger entry',
                ]);
            }
        }

        if (!$hasOpeningLedger && (float) $customer->opening_balance > 0) {
            $entries->push([
                'date' => $customer->created_at ?? now(),
                'reference' => 'Opening Balance',
                'type' => 'Opening Due',
                'debit' => (float) $customer->opening_balance,
                'credit' => 0.0,
                'note' => 'Opening customer balance carried into the ledger.',
            ]);
        }

        foreach ($customer->salesOrders as $salesOrder) {
            if (!$ledgerOrderIds->contains((int) $salesOrder->id)) {
                $entries->push([
                    'date' => $salesOrder->order_date ?? $salesOrder->created_at,
                    'reference' => $salesOrder->order_no ?: ('SO-' . $salesOrder->id),
                    'type' => 'Sales Order',
                    'debit' => (float) $salesOrder->grand_total,
                    'credit' => 0.0,
                    'note' => 'Sales order booked for customer.',
                ]);
            }
        }

        foreach ($customer->payments as $payment) {
            if ($payment->payment_direction !== 'in' || $ledgerPaymentIds->contains((int) $payment->id)) {
                continue;
            }

            $entries->push([
                'date' => $payment->payment_date ?? $payment->created_at,
                'reference' => $payment->reference_no ?: ('PAY-' . $payment->id),
                'type' => 'Collection',
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'note' => $payment->note ?: 'Collection received from customer.',
            ]);
        }

        foreach ($customer->salesReturns as $salesReturn) {
            if ($ledgerReturnIds->contains((int) $salesReturn->id)) {
                continue;
            }

            $entries->push([
                'date' => $salesReturn->return_date ?? $salesReturn->created_at,
                'reference' => $salesReturn->return_no ?: ('SR-' . $salesReturn->id),
                'type' => 'Sales Return',
                'debit' => 0.0,
                'credit' => (float) $salesReturn->refund_total,
                'note' => 'Return/refund adjusted against customer balance.',
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

    private function formatLedgerReference(CustomerLedger $entry, BaseCollection $orderReferenceMap): string
    {
        return match ((string) $entry->reference_type) {
            'sales_order' => $orderReferenceMap->get((int) $entry->reference_id, 'SO #' . $entry->reference_id),
            'opening_balance' => 'Opening Balance',
            'sales_return' => 'SR #' . $entry->reference_id,
            'payment' => 'PAY #' . $entry->reference_id,
            default => strtoupper((string) $entry->reference_type) . ' #' . $entry->reference_id,
        };
    }

    private function formatLedgerType(?string $referenceType): string
    {
        return match ((string) $referenceType) {
            'opening_balance' => 'Opening Due',
            'sales_order' => 'Sales Order',
            'sales_return' => 'Sales Return',
            'payment' => 'Collection',
            default => ucwords(str_replace('_', ' ', (string) $referenceType)),
        };
    }

    private function applySalesReportFilters($query, ?int $branchId, Request $request)
    {
        return $query
            ->whereIn('status', ['delivered', 'completed', 'processing', 'confirmed', 'packed', 'shipped'])
            ->when($branchId, fn ($salesQuery, $selectedBranchId) => $salesQuery->where('branch_id', $selectedBranchId))
            ->when($request->filled('salesChannel'), fn ($salesQuery) => $salesQuery->where('sales_channel', $request->input('salesChannel')))
            ->when($request->filled('startDate'), fn ($salesQuery) => $salesQuery->whereDate('order_date', '>=', $request->input('startDate')))
            ->when($request->filled('endDate'), fn ($salesQuery) => $salesQuery->whereDate('order_date', '<=', $request->input('endDate')));
    }

    private function customerSalesReportCustomers(Request $request)
    {
        $branchId = $this->resolveReportBranchId($request);
        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;

        return Customer::query()
            ->with([
                'customerGroup:id,name',
                'salesOrders' => function ($query) use ($branchId, $request) {
                    $this->applySalesReportFilters($query, $branchId, $request)
                        ->with(['returns:id,sales_order_id,refund_total,return_date']);
                },
            ])
            ->when($customerId, fn ($query) => $query->where('id', $customerId))
            ->whereHas('salesOrders', function ($query) use ($branchId, $request) {
                $this->applySalesReportFilters($query, $branchId, $request);
            })
            ->orderBy('name')
            ->get();
    }

    private function customerSalesReportRows($customers): BaseCollection
    {
        return $customers->map(function (Customer $customer) {
            $orders = $customer->salesOrders;
            $salesTotal = (float) $orders->sum('grand_total');
            $paidTotal = (float) $orders->sum('paid_total');
            $dueTotal = (float) $orders->sum('due_total');
            $returnTotal = (float) $orders->flatMap->returns->sum('refund_total');
            $salesChannels = $orders
                ->pluck('sales_channel')
                ->filter()
                ->unique()
                ->map(fn ($channel) => SalesOrder::channelOptions()[$channel] ?? ucfirst((string) $channel))
                ->implode(', ');

            return [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code ?: 'N/A',
                'customer_name' => $customer->name,
                'group_name' => $customer->customerGroup?->name ?? 'Ungrouped',
                'sales_channels' => $salesChannels ?: 'N/A',
                'order_count' => $orders->count(),
                'sales_total' => Currency::format($salesTotal),
                'sales_total_raw' => round($salesTotal, 2),
                'paid_total' => Currency::format($paidTotal),
                'paid_total_raw' => round($paidTotal, 2),
                'due_total' => Currency::format($dueTotal),
                'due_total_raw' => round($dueTotal, 2),
                'return_total' => Currency::format($returnTotal),
                'return_total_raw' => round($returnTotal, 2),
                'last_order_date' => DateFormatter::date($orders->sortByDesc(fn ($order) => Carbon::parse($order->order_date ?? $order->created_at))->first()?->order_date, 'N/A'),
            ];
        });
    }

    private function generateCustomerCode(): string
    {
        $nextId = (int) Customer::query()->max('id') + 1;

        return 'CUS-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
