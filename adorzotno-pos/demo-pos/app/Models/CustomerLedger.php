<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CustomerLedger extends Model
{
    protected $table = 'customer_ledger';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'branch_id',
        'entry_date',
        'reference_type',
        'reference_id',
        'debit',
        'credit',
        'balance_after',
        'remarks',
        'created_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'branch_id' => 'integer',
        'reference_id' => 'integer',
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public static function syncOpeningBalance(Customer $customer): void
    {
        $openingBalance = round((float) $customer->opening_balance, 2);

        if ($openingBalance <= 0) {
            self::query()
                ->where('customer_id', $customer->id)
                ->where('reference_type', 'opening_balance')
                ->where('reference_id', $customer->id)
                ->delete();

            self::rebuildBalancesForCustomer((int) $customer->id);

            return;
        }

        self::query()->updateOrCreate(
            [
                'customer_id' => $customer->id,
                'reference_type' => 'opening_balance',
                'reference_id' => $customer->id,
            ],
            [
                'branch_id' => null,
                'entry_date' => optional($customer->created_at)->toDateString() ?? now()->toDateString(),
                'debit' => $openingBalance,
                'credit' => 0,
                'balance_after' => 0,
                'remarks' => 'Opening customer balance',
                'created_at' => $customer->created_at ?? now(),
            ]
        );

        self::rebuildBalancesForCustomer((int) $customer->id);
    }

    public static function syncSalesOrderEntry(SalesOrder $salesOrder): void
    {
        if (empty($salesOrder->customer_id)) {
            return;
        }

        self::query()->updateOrCreate(
            [
                'customer_id' => $salesOrder->customer_id,
                'reference_type' => 'sales_order',
                'reference_id' => $salesOrder->id,
            ],
            [
                'branch_id' => $salesOrder->branch_id,
                'entry_date' => optional($salesOrder->order_date)->toDateString() ?? now()->toDateString(),
                'debit' => round((float) $salesOrder->grand_total, 2),
                'credit' => 0,
                'balance_after' => 0,
                'remarks' => 'Sales order ' . ($salesOrder->order_no ?: ('SO-' . $salesOrder->id)),
                'created_at' => $salesOrder->created_at ?? now(),
            ]
        );

        self::rebuildBalancesForCustomer((int) $salesOrder->customer_id);
    }

    public static function syncCollectionEntry(Payment $payment): void
    {
        if (empty($payment->customer_id) || $payment->payment_direction !== 'in') {
            return;
        }

        self::query()->updateOrCreate(
            [
                'customer_id' => $payment->customer_id,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
            ],
            [
                'branch_id' => $payment->branch_id,
                'entry_date' => optional($payment->payment_date)->toDateString() ?? now()->toDateString(),
                'debit' => 0,
                'credit' => round((float) $payment->amount, 2),
                'balance_after' => 0,
                'remarks' => $payment->note ?: ('Collection ' . ($payment->reference_no ?: ('PAY-' . $payment->id))),
                'created_at' => $payment->created_at ?? now(),
            ]
        );

        self::rebuildBalancesForCustomer((int) $payment->customer_id);
    }

    public static function syncSalesReturnEntry(SalesReturn $salesReturn): void
    {
        $customerId = (int) ($salesReturn->salesOrder?->customer_id ?? 0);

        if ($customerId <= 0) {
            return;
        }

        self::query()->updateOrCreate(
            [
                'customer_id' => $customerId,
                'reference_type' => 'sales_return',
                'reference_id' => $salesReturn->id,
            ],
            [
                'branch_id' => $salesReturn->salesOrder?->branch_id,
                'entry_date' => optional($salesReturn->return_date)->toDateString() ?? now()->toDateString(),
                'debit' => 0,
                'credit' => round((float) $salesReturn->refund_total, 2),
                'balance_after' => 0,
                'remarks' => 'Sales return ' . ($salesReturn->return_no ?: ('SR-' . $salesReturn->id)),
                'created_at' => $salesReturn->created_at ?? now(),
            ]
        );

        self::rebuildBalancesForCustomer($customerId);
    }

    public static function currentBalanceForCustomer(int $customerId): float
    {
        $lastEntry = self::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->first();

        if ($lastEntry !== null) {
            return round((float) $lastEntry->balance_after, 2);
        }

        return round((float) (Customer::query()->find($customerId)?->current_due ?? 0), 2);
    }

    public static function rebuildBalancesForCustomer(int $customerId): void
    {
        $runningBalance = 0.0;

        self::query()
            ->where('customer_id', $customerId)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get()
            ->each(function (CustomerLedger $entry) use (&$runningBalance) {
                $runningBalance += (float) $entry->debit;
                $runningBalance -= (float) $entry->credit;

                $entry->forceFill([
                    'balance_after' => round($runningBalance, 2),
                ])->save();
            });

        Customer::query()
            ->where('id', $customerId)
            ->update(['current_due' => round($runningBalance, 2)]);
    }
}
