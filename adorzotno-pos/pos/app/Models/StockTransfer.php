<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    protected $table = 'stock_transfers';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'transfer_no',
        'transfer_date',
        'from_branch_id',
        'from_warehouse_id',
        'to_branch_id',
        'to_warehouse_id',
        'note',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'from_branch_id' => 'integer',
        'from_warehouse_id' => 'integer',
        'to_branch_id' => 'integer',
        'to_warehouse_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id', 'id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id', 'id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id', 'id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id', 'id');
    }
}
