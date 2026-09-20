<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = "purchase_order_items";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = [
        'purchase_order_id',
        'sku_id',
        'quantity',
        'received_quantity',
        'returned_quantity',
        'unit_cost',
        'discount_amount',
        'tax_amount',
        'line_total',
        'expiry_date',
        'batch_no',     
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'returned_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }
}
