<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingJournal;
use App\Support\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    private array $bankTypes = ['bank', 'mobile_banking'];

    // ── Chart of Accounts ────────────────────────────────────────────────

    public function show()
    {
        return view('account.index');
    }

    public function list(Request $request)
    {
        $accounts = Account::query()
            ->withSum('journalLines as debit_total', 'debit')
            ->withSum('journalLines as credit_total', 'credit')
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->filter_type, fn ($q, $v) => $q->where('account_type', $v))
            ->orderBy('account_type')
            ->orderBy('account_name');

        return DataTables()->of($accounts)
            ->addColumn('type_label', fn (Account $a) => ucwords(str_replace('_', ' ', $a->account_type)))
            ->addColumn('opening_balance_display', fn (Account $a) => Currency::format($a->opening_balance))
            ->addColumn('ledger_balance', fn (Account $a) => Currency::format($this->calculateBalance($a)))
            ->addColumn('status_badge', fn (Account $a) => $a->status === 'active'
                ? '<span style="background:#d1fae5;color:#065f46;font-size:.68rem;font-weight:800;padding:2px 9px;border-radius:5px">Active</span>'
                : '<span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:800;padding:2px 9px;border-radius:5px">Inactive</span>')
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        $bankAccounts = Account::whereIn('account_type', $this->bankTypes)->where('status', 'active')->get(['id', 'account_name', 'account_type']);
        return view('account.create', ['types' => $this->accountTypes(), 'bankAccounts' => $bankAccounts]);
    }

    public function store(Request $request)
    {
        Account::query()->create($this->validateAccount($request));
        return redirect()->route('account.show')->with('success', 'Account created successfully.');
    }

    public function edit(int $id)
    {
        $account = Account::query()->findOrFail($id);
        $bankAccounts = Account::whereIn('account_type', $this->bankTypes)->where('status', 'active')->get(['id', 'account_name', 'account_type']);
        return view('account.edit', ['account' => $account, 'types' => $this->accountTypes(), 'bankAccounts' => $bankAccounts]);
    }

    public function update(Request $request, int $id)
    {
        $account = Account::query()->findOrFail($id);
        $account->update($this->validateAccount($request, $id));
        Session::flash('success', 'Account updated successfully.');
        return redirect()->route('account.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => ['required', 'exists:accounts,id']]);
        $account = Account::query()->findOrFail($validated['id']);

        if ($account->journalLines()->exists()) {
            return response()->json(['message' => 'This account has journal entries and cannot be deleted.'], 422);
        }

        $account->delete();
        return response()->json(['success' => 'Account deleted successfully.']);
    }

    // ── Bank Accounts Dashboard ──────────────────────────────────────────

    public function banks()
    {
        $bankAccounts = Account::query()
            ->whereIn('account_type', $this->bankTypes)
            ->where('status', 'active')
            ->withSum('journalLines as debit_total', 'debit')
            ->withSum('journalLines as credit_total', 'credit')
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get()
            ->map(function (Account $a) {
                $a->current_balance_live = $this->calculateBalance($a);
                return $a;
            });

        $totalBalance  = $bankAccounts->sum('current_balance_live');
        $cashAccounts  = Account::where('account_type', 'cash')->where('status', 'active')
            ->withSum('journalLines as debit_total', 'debit')
            ->withSum('journalLines as credit_total', 'credit')
            ->get()
            ->map(fn ($a) => $this->calculateBalance($a));
        $totalCash = $cashAccounts->sum();

        $otherAccounts = Account::where('account_type', 'mobile_banking')->where('status', 'active')
            ->withSum('journalLines as debit_total', 'debit')
            ->withSum('journalLines as credit_total', 'credit')
            ->get();

        return view('account.banks', compact('bankAccounts', 'totalBalance', 'totalCash'));
    }

    // ── Deposit ──────────────────────────────────────────────────────────

    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id'    => ['required', 'exists:accounts,id'],
            'amount'        => ['required', 'numeric', 'gt:0'],
            'date'          => ['required', 'date'],
            'source_account_id' => ['nullable', 'exists:accounts,id', 'different:account_id'],
            'description'   => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::findOrFail($validated['account_id']);
        $amount  = (float) $validated['amount'];
        $date    = Carbon::parse($validated['date']);
        $desc    = $validated['description'] ?: 'Deposit to ' . $account->account_name;

        DB::transaction(function () use ($account, $amount, $date, $desc, $validated, $request) {
            $journal = AccountingJournal::create([
                'journal_date'   => $date,
                'reference_type' => 'deposit',
                'memo'           => $desc,
                'posted_by'      => $request->user()?->id,
                'created_at'     => now(),
            ]);

            // Dr: Bank account (increases bank balance)
            $journal->lines()->create([
                'account_id'  => $account->id,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => $desc,
            ]);

            // Cr: Source account (cash in hand or specified)
            $sourceId = $validated['source_account_id']
                ?? Account::firstOrCreate(['account_code' => 'CASH'], ['account_name' => 'Cash In Hand', 'account_type' => 'cash', 'opening_balance' => 0, 'current_balance' => 0, 'status' => 'active'])->id;

            $journal->lines()->create([
                'account_id'  => $sourceId,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => $desc,
            ]);

            $this->refreshBalances([$account->id, $sourceId]);
        });

        return response()->json(['success' => true, 'message' => 'Deposit recorded successfully.']);
    }

    // ── Withdraw ─────────────────────────────────────────────────────────

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id'    => ['required', 'exists:accounts,id'],
            'amount'        => ['required', 'numeric', 'gt:0'],
            'date'          => ['required', 'date'],
            'destination_account_id' => ['nullable', 'exists:accounts,id', 'different:account_id'],
            'description'   => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::findOrFail($validated['account_id']);
        $amount  = (float) $validated['amount'];
        $date    = Carbon::parse($validated['date']);
        $desc    = $validated['description'] ?: 'Withdrawal from ' . $account->account_name;

        DB::transaction(function () use ($account, $amount, $date, $desc, $validated, $request) {
            $journal = AccountingJournal::create([
                'journal_date'   => $date,
                'reference_type' => 'withdrawal',
                'memo'           => $desc,
                'posted_by'      => $request->user()?->id,
                'created_at'     => now(),
            ]);

            // Cr: Bank account (decreases bank balance)
            $journal->lines()->create([
                'account_id'  => $account->id,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => $desc,
            ]);

            // Dr: Destination account (cash or expense)
            $destId = $validated['destination_account_id']
                ?? Account::firstOrCreate(['account_code' => 'CASH'], ['account_name' => 'Cash In Hand', 'account_type' => 'cash', 'opening_balance' => 0, 'current_balance' => 0, 'status' => 'active'])->id;

            $journal->lines()->create([
                'account_id'  => $destId,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => $desc,
            ]);

            $this->refreshBalances([$account->id, $destId]);
        });

        return response()->json(['success' => true, 'message' => 'Withdrawal recorded successfully.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function validateAccount(Request $request, ?int $accountId = null): array
    {
        return $request->validate([
            'account_name'   => ['required', 'string', 'max:255'],
            'account_type'   => ['required', Rule::in(array_keys($this->accountTypes()))],
            'account_code'   => ['nullable', 'string', 'max:50', Rule::unique('accounts', 'account_code')->ignore($accountId)],
            'bank_name'      => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bank_branch'    => ['nullable', 'string', 'max:150'],
            'routing_number' => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string'],
            'opening_balance'=> ['nullable', 'numeric'],
            'current_balance'=> ['nullable', 'numeric'],
            'status'         => ['required', 'in:active,inactive'],
        ]);
    }

    private function accountTypes(): array
    {
        return [
            'cash'           => 'Cash',
            'bank'           => 'Bank',
            'mobile_banking' => 'Mobile Banking',
            'receivable'     => 'Receivable',
            'payable'        => 'Payable',
            'expense'        => 'Expense',
            'income'         => 'Income',
            'equity'         => 'Equity',
            'asset'          => 'Asset',
            'liability'      => 'Liability',
        ];
    }

    private function calculateBalance(Account $account): float
    {
        $debit   = (float) ($account->debit_total ?? 0);
        $credit  = (float) ($account->credit_total ?? 0);
        $opening = (float) $account->opening_balance;

        return in_array($account->account_type, ['payable', 'income', 'equity', 'liability'], true)
            ? $opening + $credit - $debit
            : $opening + $debit - $credit;
    }

    private function refreshBalances(array $ids): void
    {
        Account::whereIn('id', array_filter($ids))->get()->each(function (Account $a) {
            $a->update([
                'current_balance' => $this->calculateBalance($a),
            ]);
        });
    }
}
