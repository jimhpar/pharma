<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingJournal;
use App\Models\AccountingJournalLine;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AccountingController extends Controller
{
    public function journal(Request $request)
    {
        $query = AccountingJournal::query()
            ->with(['lines.account', 'lines.branch', 'postedBy'])
            ->when($request->filled('startDate'), fn ($q) => $q->whereDate('journal_date', '>=', $request->startDate))
            ->when($request->filled('endDate'),   fn ($q) => $q->whereDate('journal_date', '<=', $request->endDate))
            ->when($request->filled('ref_type'),  fn ($q) => $q->where('reference_type', $request->ref_type))
            ->when($request->filled('search'),    fn ($q) => $q->where(fn ($sq) =>
                $sq->where('memo', 'like', '%'.$request->search.'%')
                   ->orWhere('reference_id', 'like', '%'.$request->search.'%')
            ))
            ->orderByDesc('journal_date')
            ->orderByDesc('id');

        $journals = $query->paginate(25)->withQueryString();

        // Period totals across ALL filtered journals (not just this page)
        $allIds = $query->pluck('accounting_journals.id');
        $periodTotals = \App\Models\AccountingJournalLine::whereIn('journal_id', $allIds)
            ->selectRaw('SUM(debit) as total_dr, SUM(credit) as total_cr')
            ->first();

        $now = now();
        $stats = [
            'total'      => AccountingJournal::count(),
            'this_month' => AccountingJournal::whereMonth('journal_date', $now->month)->whereYear('journal_date', $now->year)->count(),
            'manual'     => AccountingJournal::where('reference_type', 'manual')->count(),
        ];

        $refTypes = AccountingJournal::select('reference_type')->distinct()->orderBy('reference_type')->pluck('reference_type');

        return view('accounting.journal', compact('journals', 'stats', 'refTypes', 'periodTotals'));
    }

    public function createJournal()
    {
        return view('accounting.journal_create', $this->formOptions());
    }

    public function storeJournal(Request $request)
    {
        $validated = $request->validate([
            'journal_date' => ['required', 'date'],
            'memo' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'exists:accounts,id'],
            'lines.*.branch_id' => ['nullable', 'exists:branches,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $lines = collect($validated['lines'])
            ->map(function ($line) {
                $line['debit'] = (float) ($line['debit'] ?? 0);
                $line['credit'] = (float) ($line['credit'] ?? 0);
                return $line;
            })
            ->filter(fn ($line) => $line['debit'] > 0 || $line['credit'] > 0)
            ->values();

        if ($lines->contains(fn ($line) => empty($line['account_id']))) {
            throw ValidationException::withMessages([
                'lines' => 'Every debit or credit line must have an account.',
            ]);
        }

        $this->ensureBalanced($lines->all());

        DB::transaction(function () use ($validated, $lines, $request) {
            $journal = AccountingJournal::query()->create([
                'journal_date' => Carbon::parse($validated['journal_date']),
                'reference_type' => 'manual',
                'memo' => $validated['memo'] ?? null,
                'posted_by' => $request->user()?->id,
                'created_at' => now(),
            ]);

            foreach ($lines as $line) {
                $journal->lines()->create($line);
            }

            $this->refreshBalances($lines->pluck('account_id')->unique()->all());
        });

        return redirect()->route('accounting.journal')->with('success', 'Manual journal saved successfully.');
    }

    public function createTransfer()
    {
        return view('accounting.transfer', $this->formOptions());
    }

    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'transfer_date' => ['required', 'date'],
            'from_account_id' => ['required', 'exists:accounts,id', 'different:to_account_id'],
            'to_account_id' => ['required', 'exists:accounts,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $journal = AccountingJournal::query()->create([
                'journal_date' => Carbon::parse($validated['transfer_date']),
                'reference_type' => 'fund_transfer',
                'memo' => $validated['note'] ?? 'Fund transfer between accounts',
                'posted_by' => $request->user()?->id,
                'created_at' => now(),
            ]);

            $journal->lines()->create([
                'account_id' => $validated['to_account_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'debit' => $validated['amount'],
                'credit' => 0,
                'description' => 'Fund received',
            ]);

            $journal->lines()->create([
                'account_id' => $validated['from_account_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'debit' => 0,
                'credit' => $validated['amount'],
                'description' => 'Fund transferred',
            ]);

            $this->refreshBalances([$validated['from_account_id'], $validated['to_account_id']]);
        });

        return redirect()->route('accounting.journal')->with('success', 'Fund transfer posted successfully.');
    }

    public function syncAutoJournals(Request $request)
    {
        $created = 0;

        DB::transaction(function () use (&$created, $request) {
            $created += $this->syncSales($request);
            $created += $this->syncPurchases($request);
            $created += $this->syncExpenses($request);
            $created += $this->syncSalesReturns($request);
            $created += $this->syncSupplierReturns($request);
            $this->refreshAllBalances();
        });

        return redirect()->back()->with('success', $created . ' auto journal entries generated.');
    }

    public function reports(Request $request)
    {
        $report = $request->input('report', 'profit_loss');
        $data   = $this->reportData($request, $report);

        // P&L breakdown — reuse $data lines (no duplicate query)
        $plDetail = [];
        if ($report === 'profit_loss') {
            // Re-fetch lines with account relationship for grouping
            $plLines = AccountingJournalLine::query()
                ->with(['account', 'journal'])
                ->whereHas('journal', function ($q) use ($request) {
                    $q->when($request->filled('startDate'), fn ($q) => $q->whereDate('journal_date', '>=', $request->startDate))
                      ->when($request->filled('endDate'),   fn ($q) => $q->whereDate('journal_date', '<=', $request->endDate));
                })
                ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
                ->get();

            $plDetail['income'] = $plLines
                ->filter(fn ($l) => $l->account?->account_type === 'income')
                ->groupBy('account_id')
                ->map(fn ($g) => [
                    'name'   => $g->first()->account?->account_name ?? 'Unknown',
                    'amount' => $g->sum(fn ($l) => (float)$l->credit - (float)$l->debit),
                ])
                ->filter(fn ($row) => $row['amount'] != 0)
                ->sortByDesc('amount')
                ->values();

            $plDetail['expense'] = $plLines
                ->filter(fn ($l) => $l->account?->account_type === 'expense')
                ->groupBy('account_id')
                ->map(fn ($g) => [
                    'name'   => $g->first()->account?->account_name ?? 'Unknown',
                    'amount' => $g->sum(fn ($l) => (float)$l->debit - (float)$l->credit),
                ])
                ->filter(fn ($row) => $row['amount'] != 0)
                ->sortByDesc('amount')
                ->values();
        }

        // Financial health overview (always current balances)
        $now = now();
        $overview = [
            'cash'       => (float) Account::where('account_type', 'cash')->sum('current_balance'),
            'bank'       => (float) Account::whereIn('account_type', ['bank', 'mobile_banking'])->sum('current_balance'),
            'receivable' => (float) Account::where('account_type', 'receivable')->sum('current_balance'),
            'payable'    => (float) Account::where('account_type', 'payable')->sum('current_balance'),
            'month_income' => (float) AccountingJournalLine::query()
                ->whereHas('account', fn ($q) => $q->where('account_type', 'income'))
                ->whereHas('journal', fn ($q) => $q->whereMonth('journal_date', $now->month)->whereYear('journal_date', $now->year))
                ->selectRaw('SUM(credit) - SUM(debit) as total')
                ->value('total'),
            'month_expense' => (float) AccountingJournalLine::query()
                ->whereHas('account', fn ($q) => $q->where('account_type', 'expense'))
                ->whereHas('journal', fn ($q) => $q->whereMonth('journal_date', $now->month)->whereYear('journal_date', $now->year))
                ->selectRaw('SUM(debit) - SUM(credit) as total')
                ->value('total'),
        ];
        $overview['month_profit'] = $overview['month_income'] - $overview['month_expense'];

        return view('accounting.reports', array_merge($this->formOptions(), compact('report', 'data', 'overview', 'plDetail')));
    }

    public function reportsExcel(Request $request)
    {
        $report = $request->input('report', 'profit_loss');
        $data = $this->reportData($request, $report);

        if ($report === 'profit_loss') {
            return $this->downloadCsv('profit-loss-report-' . now()->format('Y-m-d-His'), ['Particular', 'Amount'], [
                ['Total Income', round((float) $data['income'], 2)],
                ['Total Expense', round((float) $data['expense'], 2)],
                ['Net Profit / Loss', round((float) $data['net_profit'], 2)],
            ]);
        }

        if (in_array($report, ['receivable', 'payable'], true)) {
            $rows = $data['rows']->map(fn ($row) => [
                $row->name,
                $row->phone,
                round((float) ($row->due_total ?? $row->current_due ?? 0), 2),
            ]);

            return $this->downloadCsv($report . '-report-' . now()->format('Y-m-d-His'), ['Name', 'Phone', 'Total Due'], $rows);
        }

        $rows = $data['lines']->map(fn (AccountingJournalLine $line) => [
            DateFormatter::date($line->journal?->journal_date),
            $line->account?->account_name,
            $line->branch?->name ?? 'Global',
            trim(($line->journal?->reference_type ?? '') . ' ' . ($line->journal?->reference_id ? '#' . $line->journal?->reference_id : '')),
            $line->description,
            round((float) $line->debit, 2),
            round((float) $line->credit, 2),
        ]);

        return $this->downloadCsv($report . '-report-' . now()->format('Y-m-d-His'), [
            'Date', 'Account', 'Branch', 'Reference', 'Description', 'Debit', 'Credit',
        ], $rows);
    }

    private function syncSales(Request $request): int
    {
        $salesAccount = $this->account('Sales Income', 'income', 'SALES');
        $cashAccount = $this->account('Cash In Hand', 'cash', 'CASH');
        $receivableAccount = $this->account('Customer Receivable', 'receivable', 'AR');
        $created = 0;

        SalesOrder::query()
            ->whereIn('status', ['delivered', 'completed'])
            ->chunkById(100, function ($orders) use ($salesAccount, $cashAccount, $receivableAccount, &$created, $request) {
                foreach ($orders as $order) {
                    if ($this->journalExists('sale', $order->id)) {
                        continue;
                    }

                    $journal = $this->createAutoJournal($order->order_date, 'sale', $order->id, 'Auto journal for sale ' . $order->order_no, $request);
                    $paid = (float) $order->paid_total;
                    $due = (float) $order->due_total;
                    $total = (float) $order->grand_total;

                    if ($paid > 0) {
                        $this->line($journal, $cashAccount, $order->branch_id, $paid, 0, 'Cash/bank received from sale');
                    }
                    if ($due > 0) {
                        $this->line($journal, $receivableAccount, $order->branch_id, $due, 0, 'Customer receivable');
                    }
                    if ($total > 0) {
                        $this->line($journal, $salesAccount, $order->branch_id, 0, $total, 'Sales revenue');
                    }
                    $created++;
                }
            });

        return $created;
    }

    private function syncPurchases(Request $request): int
    {
        $inventoryAccount = $this->account('Inventory Asset', 'asset', 'INV');
        $cashAccount = $this->account('Cash In Hand', 'cash', 'CASH');
        $payableAccount = $this->account('Supplier Payable', 'payable', 'AP');
        $created = 0;

        PurchaseOrder::query()
            ->whereIn('status', ['received', 'partial_received'])
            ->chunkById(100, function ($purchases) use ($inventoryAccount, $cashAccount, $payableAccount, &$created, $request) {
                foreach ($purchases as $purchase) {
                    if ($this->journalExists('purchase', $purchase->id)) {
                        continue;
                    }

                    $journal = $this->createAutoJournal($purchase->purchase_date, 'purchase', $purchase->id, 'Auto journal for purchase ' . $purchase->purchase_no, $request);
                    $paid = (float) $purchase->paid_total;
                    $due = (float) $purchase->due_total;
                    $total = (float) $purchase->grand_total;

                    if ($total > 0) {
                        $this->line($journal, $inventoryAccount, $purchase->branch_id, $total, 0, 'Inventory purchased');
                    }
                    if ($paid > 0) {
                        $this->line($journal, $cashAccount, $purchase->branch_id, 0, $paid, 'Payment made');
                    }
                    if ($due > 0) {
                        $this->line($journal, $payableAccount, $purchase->branch_id, 0, $due, 'Supplier payable');
                    }
                    $created++;
                }
            });

        return $created;
    }

    private function syncExpenses(Request $request): int
    {
        $cashAccount = $this->account('Cash In Hand', 'cash', 'CASH');
        $created = 0;

        Expense::query()
            ->with('category')
            ->chunkById(100, function ($expenses) use ($cashAccount, &$created, $request) {
                foreach ($expenses as $expense) {
                    if ($this->journalExists('expense', $expense->id)) {
                        continue;
                    }

                    $expenseAccount = $this->account('Expense - ' . ($expense->category?->name ?? 'General'), 'expense', null);
                    $sourceAccount = $this->paymentSourceAccount($expense->payment_source) ?? $cashAccount;
                    $journal = $this->createAutoJournal($expense->expense_date, 'expense', $expense->id, 'Auto journal for expense #' . $expense->id, $request);

                    $this->line($journal, $expenseAccount, $expense->branch_id, (float) $expense->amount, 0, $expense->note ?: 'Expense');
                    $this->line($journal, $sourceAccount, $expense->branch_id, 0, (float) $expense->amount, 'Paid from ' . ($expense->payment_source ?: 'cash'));
                    $created++;
                }
            });

        return $created;
    }

    private function syncSalesReturns(Request $request): int
    {
        $salesAccount = $this->account('Sales Income', 'income', 'SALES');
        $cashAccount = $this->account('Cash In Hand', 'cash', 'CASH');
        $created = 0;

        SalesReturn::query()
            ->with('salesOrder')
            ->whereIn('status', ['approved', 'completed'])
            ->chunkById(100, function ($returns) use ($salesAccount, $cashAccount, &$created, $request) {
                foreach ($returns as $return) {
                    if ($this->journalExists('sales_return', $return->id)) {
                        continue;
                    }

                    $amount = (float) $return->refund_total;
                    if ($amount <= 0) {
                        continue;
                    }

                    $journal = $this->createAutoJournal($return->return_date, 'sales_return', $return->id, 'Reverse sale return ' . $return->return_no, $request);
                    $this->line($journal, $salesAccount, $return->salesOrder?->branch_id, $amount, 0, 'Sales return reversal');
                    $this->line($journal, $cashAccount, $return->salesOrder?->branch_id, 0, $amount, 'Refund paid');
                    $created++;
                }
            });

        return $created;
    }

    private function syncSupplierReturns(Request $request): int
    {
        $inventoryAccount = $this->account('Inventory Asset', 'asset', 'INV');
        $payableAccount = $this->account('Supplier Payable', 'payable', 'AP');
        $created = 0;

        SupplierReturn::query()
            ->with('items')
            ->chunkById(100, function ($returns) use ($inventoryAccount, $payableAccount, &$created, $request) {
                foreach ($returns as $return) {
                    if ($this->journalExists('supplier_return', $return->id)) {
                        continue;
                    }

                    $amount = (float) $return->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_cost);
                    if ($amount <= 0) {
                        continue;
                    }

                    $journal = $this->createAutoJournal($return->return_date, 'supplier_return', $return->id, 'Reverse supplier return ' . $return->return_no, $request);
                    $this->line($journal, $payableAccount, $return->branch_id, $amount, 0, 'Supplier payable reduced');
                    $this->line($journal, $inventoryAccount, $return->branch_id, 0, $amount, 'Inventory returned');
                    $created++;
                }
            });

        return $created;
    }

    private function reportData(Request $request, string $report): array
    {
        // ── Credit-normal account types ──────────────────────────────────
        $creditNormalTypes = ['payable', 'income', 'equity', 'liability'];

        // ── Trial Balance ────────────────────────────────────────────────
        if ($report === 'trial_balance') {
            $accounts = Account::query()
                ->where('status', 'active')
                ->orderBy('account_type')
                ->orderBy('account_name')
                ->get();

            $rows = $accounts->map(function (Account $account) use ($request, $creditNormalTypes) {
                $q = $account->journalLines()
                    ->whereHas('journal', fn ($jq) => $jq
                        ->when($request->filled('startDate'), fn ($q) => $q->whereDate('journal_date', '>=', $request->startDate))
                        ->when($request->filled('endDate'),   fn ($q) => $q->whereDate('journal_date', '<=', $request->endDate))
                    )
                    ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id));

                $totalDebit  = (float) $q->sum('debit');
                $totalCredit = (float) $q->sum('credit');
                $net         = $totalDebit - $totalCredit;

                // In trial balance: net goes to debit or credit column
                $isCreditNormal = in_array($account->account_type, $creditNormalTypes, true);
                if ($isCreditNormal) {
                    $tbDebit  = $net > 0 ? $net  : 0;      // abnormal debit on credit-normal account
                    $tbCredit = $net < 0 ? -$net : 0;      // normal credit balance
                } else {
                    $tbDebit  = $net > 0 ? $net  : 0;      // normal debit balance
                    $tbCredit = $net < 0 ? -$net : 0;      // abnormal credit on debit-normal account
                }

                return [
                    'code'    => $account->account_code ?? '—',
                    'name'    => $account->account_name,
                    'type'    => $account->account_type,
                    'debit'   => $tbDebit,
                    'credit'  => $tbCredit,
                ];
            })->filter(fn ($r) => $r['debit'] > 0 || $r['credit'] > 0)->values();

            return [
                'rows'         => $rows,
                'total_debit'  => $rows->sum('debit'),
                'total_credit' => $rows->sum('credit'),
                'balanced'     => round($rows->sum('debit'), 2) === round($rows->sum('credit'), 2),
            ];
        }

        // ── Balance Sheet ────────────────────────────────────────────────
        if ($report === 'balance_sheet') {
            $accounts = Account::query()
                ->where('status', 'active')
                ->get();

            $calcBalance = function (Account $account) use ($request, $creditNormalTypes) {
                $q = $account->journalLines()
                    ->whereHas('journal', fn ($jq) => $jq
                        ->when($request->filled('endDate'), fn ($q) => $q->whereDate('journal_date', '<=', $request->endDate))
                    )
                    ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id));
                $debit  = (float) $q->sum('debit');
                $credit = (float) $q->sum('credit');
                $opening = (float) $account->opening_balance;
                $isCreditNormal = in_array($account->account_type, $creditNormalTypes, true);
                return $isCreditNormal
                    ? $opening + $credit - $debit
                    : $opening + $debit - $credit;
            };

            $assetTypes     = ['cash', 'bank', 'mobile_banking', 'asset', 'receivable'];
            $liabilityTypes = ['payable', 'liability'];
            $equityTypes    = ['equity'];

            $assets = $accounts->filter(fn ($a) => in_array($a->account_type, $assetTypes))
                ->map(fn ($a) => ['name' => $a->account_name, 'type' => $a->account_type, 'balance' => $calcBalance($a)])
                ->filter(fn ($r) => $r['balance'] != 0)->values();

            $liabilities = $accounts->filter(fn ($a) => in_array($a->account_type, $liabilityTypes))
                ->map(fn ($a) => ['name' => $a->account_name, 'type' => $a->account_type, 'balance' => $calcBalance($a)])
                ->filter(fn ($r) => $r['balance'] != 0)->values();

            $equity = $accounts->filter(fn ($a) => in_array($a->account_type, $equityTypes))
                ->map(fn ($a) => ['name' => $a->account_name, 'type' => $a->account_type, 'balance' => $calcBalance($a)])
                ->filter(fn ($r) => $r['balance'] != 0)->values();

            // Retained earnings = net income (all-time or up to endDate)
            $incomeLines  = AccountingJournalLine::query()
                ->whereHas('account', fn ($q) => $q->where('account_type', 'income'))
                ->whereHas('journal', fn ($q) => $request->filled('endDate') ? $q->whereDate('journal_date', '<=', $request->endDate) : $q)
                ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id));
            $expenseLines = AccountingJournalLine::query()
                ->whereHas('account', fn ($q) => $q->where('account_type', 'expense'))
                ->whereHas('journal', fn ($q) => $request->filled('endDate') ? $q->whereDate('journal_date', '<=', $request->endDate) : $q)
                ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id));

            $retainedEarnings = ((float) $incomeLines->sum('credit') - (float) $incomeLines->sum('debit'))
                - ((float) $expenseLines->sum('debit') - (float) $expenseLines->sum('credit'));

            $totalAssets      = $assets->sum('balance');
            $totalLiabilities = $liabilities->sum('balance');
            $totalEquity      = $equity->sum('balance') + $retainedEarnings;

            return [
                'assets'           => $assets,
                'liabilities'      => $liabilities,
                'equity'           => $equity,
                'retained_earnings'=> $retainedEarnings,
                'total_assets'     => $totalAssets,
                'total_liabilities'=> $totalLiabilities,
                'total_equity'     => $totalEquity,
                'balanced'         => round($totalAssets, 2) === round($totalLiabilities + $totalEquity, 2),
            ];
        }

        // ── Shared lines query (P&L, Ledgers, Books) ────────────────────
        $lines = AccountingJournalLine::query()
            ->with(['account', 'branch', 'journal'])
            ->whereHas('journal', function ($query) use ($request) {
                $query
                    ->when($request->filled('startDate'), fn ($q) => $q->whereDate('journal_date', '>=', $request->startDate))
                    ->when($request->filled('endDate'),   fn ($q) => $q->whereDate('journal_date', '<=', $request->endDate));
            })
            ->when($request->filled('branch_id'),  fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->filled('account_id'), fn ($query) => $query->where('account_id', $request->account_id))
            ->orderBy(AccountingJournal::select('journal_date')->whereColumn('accounting_journals.id', 'accounting_journal_lines.journal_id'))
            ->orderBy('id')
            ->get();

        if ($report === 'profit_loss') {
            $income  = $lines->filter(fn ($l) => $l->account?->account_type === 'income')
                ->sum(fn ($l) => (float) $l->credit - (float) $l->debit);
            $expense = $lines->filter(fn ($l) => $l->account?->account_type === 'expense')
                ->sum(fn ($l) => (float) $l->debit - (float) $l->credit);

            return ['income' => $income, 'expense' => $expense, 'net_profit' => $income - $expense];
        }

        if ($report === 'receivable') {
            return ['rows' => Customer::query()->withSum('salesOrders as due_total', 'due_total')->orderByDesc('due_total')->get()];
        }

        if ($report === 'payable') {
            return ['rows' => Supplier::query()->withSum('purchaseOrders as due_total', 'due_total')->orderByDesc('due_total')->get()];
        }

        $allowedTypes = match ($report) {
            'cash_book' => ['cash'],
            'bank_book' => ['bank', 'mobile_banking'],
            default     => [],
        };

        if ($allowedTypes !== []) {
            $lines = $lines->filter(fn ($l) => in_array($l->account?->account_type, $allowedTypes, true))->values();
        }

        // ── Opening Balance (for date-filtered single-account/book views) ──
        $openingBalance  = 0.0;
        $isSingleView    = in_array($report, ['cash_book', 'bank_book', 'account_ledger'], true);
        $firstAccType    = $lines->first()?->account?->account_type ?? 'asset';
        $isCreditNormal  = in_array($firstAccType, $creditNormalTypes, true);

        if ($isSingleView && $request->filled('startDate')) {
            $obQuery = AccountingJournalLine::query()
                ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '<', $request->startDate))
                ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id));

            if ($report === 'account_ledger' && $request->filled('account_id')) {
                $obQuery->where('account_id', $request->account_id);
                $acc = Account::find($request->account_id);
                $isCreditNormal = in_array($acc?->account_type, $creditNormalTypes, true);
                $openingBalance  = (float) ($acc?->opening_balance ?? 0);
            } else {
                // Cash book / Bank book: all accounts of that type
                $obQuery->whereHas('account', fn ($q) => $q->whereIn('account_type', $allowedTypes ?: ['cash']));
                // Sum opening_balance of relevant accounts
                $openingBalance = (float) Account::whereIn('account_type', $allowedTypes ?: ['cash'])->sum('opening_balance');
            }

            $obDebit  = (float) $obQuery->sum('debit');
            $obCredit = (float) $obQuery->sum('credit');
            $openingBalance += $isCreditNormal
                ? ($obCredit - $obDebit)
                : ($obDebit  - $obCredit);
        }

        return [
            'lines'            => $lines,
            'debit'            => $lines->sum('debit'),
            'credit'           => $lines->sum('credit'),
            'show_balance'     => $isSingleView,
            'is_credit_normal' => $isCreditNormal,
            'opening_balance'  => $openingBalance,
        ];
    }

    private function formOptions(): array
    {
        return [
            'accounts' => Account::query()->where('status', 'active')->orderBy('account_name')->get(),
            'branches' => $this->branchContext()->accessibleBranches(auth()->user()),
            'currency' => Currency::class,
        ];
    }

    private function ensureBalanced(array $lines): void
    {
        $debit = collect($lines)->sum('debit');
        $credit = collect($lines)->sum('credit');

        if (round($debit, 2) <= 0 || round($debit, 2) !== round($credit, 2)) {
            throw ValidationException::withMessages([
                'lines' => 'Journal debit and credit totals must be equal and greater than zero.',
            ]);
        }
    }

    private function account(string $name, string $type, ?string $code): Account
    {
        return Account::query()->firstOrCreate(
            $code ? ['account_code' => $code] : ['account_name' => $name],
            [
                'account_name' => $name,
                'account_type' => $type,
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => 'active',
            ]
        );
    }

    private function paymentSourceAccount(?string $source): ?Account
    {
        $source = trim((string) $source);
        if ($source === '') {
            return null;
        }

        $type = str_contains(strtolower($source), 'bank')
            ? 'bank'
            : (preg_match('/bkash|nagad|rocket|mobile/i', $source) ? 'mobile_banking' : 'cash');

        return $this->account($source, $type, null);
    }

    private function journalExists(string $type, int $id): bool
    {
        return AccountingJournal::query()->where('reference_type', $type)->where('reference_id', $id)->exists();
    }

    private function createAutoJournal($date, string $type, int $id, string $memo, Request $request): AccountingJournal
    {
        return AccountingJournal::query()->create([
            'journal_date' => Carbon::parse($date),
            'reference_type' => $type,
            'reference_id' => $id,
            'memo' => $memo,
            'posted_by' => $request->user()?->id,
            'created_at' => now(),
        ]);
    }

    private function line(AccountingJournal $journal, Account $account, ?int $branchId, float $debit, float $credit, string $description): void
    {
        $journal->lines()->create([
            'account_id' => $account->id,
            'branch_id' => $branchId,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
        ]);
    }

    private function refreshBalances(array $accountIds): void
    {
        Account::query()
            ->whereIn('id', array_filter($accountIds))
            ->get()
            ->each(fn (Account $account) => $account->update([
                'current_balance' => $this->calculateAccountBalance($account),
            ]));
    }

    private function refreshAllBalances(): void
    {
        Account::query()
            ->get()
            ->each(fn (Account $account) => $account->update([
                'current_balance' => $this->calculateAccountBalance($account),
            ]));
    }

    private function calculateAccountBalance(Account $account): float
    {
        $debit = (float) $account->journalLines()->sum('debit');
        $credit = (float) $account->journalLines()->sum('credit');
        $opening = (float) $account->opening_balance;

        return in_array($account->account_type, ['payable', 'income', 'equity', 'liability'], true)
            ? $opening + $credit - $debit
            : $opening + $debit - $credit;
    }
}
