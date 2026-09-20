<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $table = "sales_returns";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'sales_order_id',
        'return_no',
        'return_date',
        'refund_total',
        'status',
        'reason',
        'approved_by',
        'processed_by',
    ];

    protected $casts = [
        'sales_order_id' => 'integer',
        'return_date' => 'datetime',
        'refund_total' => 'decimal:2',
        'approved_by' => 'integer',
        'processed_by' => 'integer',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id', 'id');
    }
}
