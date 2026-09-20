<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAdjustment extends Model
{
    use HasFactory;

    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_OPENING_STOCK = 'opening_stock';
    public const TYPE_STOCK_ISSUE = 'stock_issue';

    protected $table = 'inventory_adjustments';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'document_type',
        'adjustment_no',
        'adjustment_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'adjustment_date' => 'date',
        'created_by' => 'integer',
    ];

    public static function documentTypes(): array
    {
        return [
            self::TYPE_ADJUSTMENT,
            self::TYPE_OPENING_STOCK,
            self::TYPE_STOCK_ISSUE,
        ];
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ((string) $this->document_type) {
            self::TYPE_OPENING_STOCK => 'Opening Stock',
            self::TYPE_STOCK_ISSUE => 'Stock Issue',
            default => 'Stock Adjustment',
        };
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class, 'inventory_adjustment_id', 'id');
    }
}
