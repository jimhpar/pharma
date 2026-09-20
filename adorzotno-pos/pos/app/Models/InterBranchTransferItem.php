<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterBranchTransferItem extends Model
{
    protected $table = 'inter_branch_transfer_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'transfer_id',
        'sku_id',
        'batch_id',
        'requested_quantity',
        'approved_quantity',
        'dispatched_quantity',
        'received_quantity',
    ];

    protected $casts = [
        'transfer_id' => 'integer',
        'sku_id' => 'integer',
        'batch_id' => 'integer',
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
        'dispatched_quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InterBranchTransfer::class, 'transfer_id', 'id');
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
