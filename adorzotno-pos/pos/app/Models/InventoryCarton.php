<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCarton extends Model
{
    protected $table = 'inventory_cartons';

    protected $fillable = [
        'purchase_order_item_id',
        'sku_id',
        'warehouse_id',
        'batch_id',
        'carton_code',
        'boxes_per_carton',
        'units_per_box',
        'total_units',
        'available_units',
        'unit_cost',
        'batch_no_label',
        'expiry_date',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'boxes_per_carton' => 'integer',
        'units_per_box'    => 'integer',
        'total_units'      => 'integer',
        'available_units'  => 'integer',
        'unit_cost'        => 'decimal:2',
        'expiry_date'      => 'date',
        'received_at'      => 'datetime',
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

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id', 'id');
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(InventoryBox::class, 'carton_id', 'id');
    }

    public function openBoxes(): HasMany
    {
        return $this->hasMany(InventoryBox::class, 'carton_id', 'id')->where('status', 'open');
    }

    public function sealedBoxes(): HasMany
    {
        return $this->hasMany(InventoryBox::class, 'carton_id', 'id')->where('status', 'sealed');
    }

    public static function generateCode(int $purchaseOrderId, int $skuId): string
    {
        $prefix  = 'CTN-' . now()->format('Ymd');
        $lastSeq = static::where('carton_code', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->count();
        return $prefix . '-' . str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
    }
}
