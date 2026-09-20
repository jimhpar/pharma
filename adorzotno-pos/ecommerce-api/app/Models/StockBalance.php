<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

    protected $table = "stock_balances";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'sku_id', 
        'batch_id', 
        'available_quantity',
        'reserved_quantity',
        'reorder_level',
        'updated_at',        
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'sku_id' => 'integer',
        'batch_id' => 'integer',
        'available_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_level' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
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
