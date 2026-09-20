<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class InventorySerial extends Model
{
    use HasFactory;

    protected $table = "inventory_serials";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'sku_id',
        'warehouse_id',
        'batch_id', 
        'serial_number', 
        'status',     
              
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id', 'id');
    }
}
