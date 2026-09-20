<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $table = "inventory_batches";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'sku_id',
        'supplier_id',
        'warehouse_id',
        'batch_no',
        'purchase_price',
        'sale_price',
        'received_quantity',
        'available_quantity',
        'manufacture_date',
        'expiry_date',
        'received_at',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'received_quantity' => 'integer',
        'available_quantity' => 'integer',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'batch_id', 'id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(InventorySerial::class, 'batch_id', 'id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'batch_id', 'id');
    }
}
