<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $table = "sales_order_items";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'sales_order_id',
        'sku_id',
        'warehouse_id',
        'batch_id',
        'quantity',
        'unit_price',
        'cost_price',
        'discount_amount',
        'tax_amount',
        'line_total',
        'returned_quantity',
    ];

    protected $casts = [
        'sales_order_id' => 'integer',
        'sku_id' => 'integer',
        'warehouse_id' => 'integer',
        'batch_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'returned_quantity' => 'integer',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            Sku::class,
            'id',
            'id',
            'sku_id',
            'product_id'
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }
}
