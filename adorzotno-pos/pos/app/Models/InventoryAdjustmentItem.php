<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustmentItem extends Model
{
    protected $table = 'inventory_adjustment_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'inventory_adjustment_id',
        'sku_id',
        'batch_id',
        'adjustment_type',
        'quantity',
        'balance_before',
        'balance_after',
        'reason',
    ];

    protected $casts = [
        'inventory_adjustment_id' => 'integer',
        'sku_id' => 'integer',
        'batch_id' => 'integer',
        'quantity' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id', 'id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id', 'id');
    }
}
