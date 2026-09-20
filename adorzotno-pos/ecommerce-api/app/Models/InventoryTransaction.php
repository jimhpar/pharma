<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'inventory_transactions';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'sku_id',
        'batch_id',
        'reference_type',
        'reference_id',
        'movement_type',
        'quantity',
        'balance_after',
        'unit_cost',
        'remarks',
        'occurred_at',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'sku_id' => 'integer',
        'batch_id' => 'integer',
        'reference_id' => 'integer',
        'quantity' => 'integer',
        'balance_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'occurred_at' => 'datetime',
        'created_by' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
