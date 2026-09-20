<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $table = "sales_return_items";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'sales_return_id',
        'sales_order_item_id',
        'quantity',
        'refund_amount',      
    ];

    protected $casts = [
        'sales_return_id' => 'integer',
        'sales_order_item_id' => 'integer',
        'quantity' => 'integer',
        'refund_amount' => 'decimal:2',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id', 'id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id', 'id');
    }
}
