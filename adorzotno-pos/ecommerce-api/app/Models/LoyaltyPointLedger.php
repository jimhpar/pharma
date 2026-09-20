<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointLedger extends Model
{
    protected $table = 'loyalty_point_ledger';
    public $timestamps = false;
    public $updated_at = false;

    protected $fillable = [
        'customer_id',
        'sales_order_id',
        'type',
        'points',
        'amount_value',
        'note',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'customer_id'    => 'integer',
        'sales_order_id' => 'integer',
        'points'         => 'integer',
        'amount_value'   => 'decimal:2',
        'created_by'     => 'integer',
        'created_at'     => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earned'   => 'Earned',
            'redeemed' => 'Redeemed',
            'adjusted' => 'Adjusted',
            default    => ucfirst((string) $this->type),
        };
    }
}
