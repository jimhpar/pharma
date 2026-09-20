<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = "payments";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'sales_order_id',
        'purchase_id',
        'customer_id', 
        'supplier_id', 
        'account_id',       
        'branch_id',
        'payment_direction',
        'payment_purpose',
        'payment_method',
        'amount',
        'payment_date',
        'reference_no',
        'note',
        'received_by',
        'created_at' 
    ];

    protected $casts = [
        'sales_order_id' => 'integer',
        'purchase_id' => 'integer',
        'customer_id' => 'integer',
        'supplier_id' => 'integer',
        'account_id' => 'integer',
        'branch_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'received_by' => 'integer',
        'created_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }

    public static function normalizeMethod(?string $method): string
    {
        return match (strtolower(trim((string) $method))) {
            'cash', 'cash on delivery', 'cod' => 'cash',
            'card' => 'card',
            'bank', 'bank transfer' => 'bank',
            'mobile banking', 'mobile_banking', 'bkash', 'nagad', 'rocket' => 'mobile_banking',
            'wallet' => 'wallet',
            'cheque', 'check' => 'cheque',
            default => 'other',
        };
    }

    public static function labelForMethod(?string $method): string
    {
        return match ((string) $method) {
            'cash' => 'Cash',
            'card' => 'Card',
            'bank' => 'Bank Transfer',
            'mobile_banking' => 'Mobile Banking',
            'wallet' => 'Wallet',
            'cheque' => 'Cheque',
            default => 'Other',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::labelForMethod($this->payment_method);
    }
}
