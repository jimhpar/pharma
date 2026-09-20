<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SupplierLedger extends Model
{
    use HasFactory;

    protected $table = "supplier_ledger";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
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
        'supplier_id' => 'integer',
        'branch_id' => 'integer',
        'reference_id' => 'integer',
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public static function syncOpeningBalance(Supplier $supplier): void
    {
        $openingBalance = round((float) $supplier->current_due, 2);

        if ($openingBalance <= 0) {
            self::query()
                ->where('supplier_id', $supplier->id)
                ->where('reference_type', 'opening_balance')
                ->where('reference_id', $supplier->id)
                ->delete();

            self::rebuildBalancesForSupplier((int) $supplier->id);

            return;
        }

        self::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'reference_type' => 'opening_balance',
                'reference_id' => $supplier->id,
            ],
            [
                'branch_id' => null,
                'entry_date' => optional($supplier->created_at)->toDateString() ?? now()->toDateString(),
                'debit' => $openingBalance,
                'credit' => 0,
                'balance_after' => 0,
                'remarks' => 'Opening supplier balance',
                'created_at' => $supplier->created_at ?? now(),
            ]
        );

        self::rebuildBalancesForSupplier((int) $supplier->id);
    }

    public static function syncPurchaseOrderEntry(PurchaseOrder $purchaseOrder): void
    {
        self::query()->updateOrCreate(
            [
                'supplier_id' => $purchaseOrder->supplier_id,
                'reference_type' => 'purchase_order',
                'reference_id' => $purchaseOrder->id,
            ],
            [
                'branch_id' => $purchaseOrder->branch_id,
                'entry_date' => optional($purchaseOrder->purchase_date)->toDateString() ?? now()->toDateString(),
                'debit' => round((float) $purchaseOrder->grand_total, 2),
                'credit' => round((float) $purchaseOrder->paid_total, 2),
                'balance_after' => 0,
                'remarks' => 'Purchase order ' . $purchaseOrder->purchase_no,
                'created_at' => $purchaseOrder->created_at ?? now(),
            ]
        );

        self::rebuildBalancesForSupplier((int) $purchaseOrder->supplier_id);
    }

    public static function removePurchaseOrderEntries(PurchaseOrder $purchaseOrder): void
    {
        self::query()
            ->where('supplier_id', $purchaseOrder->supplier_id)
            ->where('reference_type', 'purchase_order')
            ->where('reference_id', $purchaseOrder->id)
            ->delete();

        self::rebuildBalancesForSupplier((int) $purchaseOrder->supplier_id);
    }

    public static function rebuildBalancesForSupplier(int $supplierId): void
    {
        $runningBalance = 0.0;

        self::query()
            ->where('supplier_id', $supplierId)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get()
            ->each(function (SupplierLedger $entry) use (&$runningBalance) {
                $runningBalance += (float) $entry->debit;
                $runningBalance -= (float) $entry->credit;

                $entry->forceFill([
                    'balance_after' => round($runningBalance, 2),
                ])->save();
            });
    }
}
