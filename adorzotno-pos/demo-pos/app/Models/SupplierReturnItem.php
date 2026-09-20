<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnItem extends Model
{
    protected $table = 'supplier_return_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'supplier_return_id',
        'sku_id',
        'batch_id',
        'quantity',
        'unit_cost',
        'reason',
    ];

    protected $casts = [
        'supplier_return_id' => 'integer',
        'sku_id' => 'integer',
        'batch_id' => 'integer',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class, 'supplier_return_id', 'id');
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
