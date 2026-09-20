<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $table = 'stock_transfer_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'stock_transfer_id',
        'sku_id',
        'source_batch_id',
        'destination_batch_id',
        'quantity',
        'note',
    ];

    protected $casts = [
        'stock_transfer_id' => 'integer',
        'sku_id' => 'integer',
        'source_batch_id' => 'integer',
        'destination_batch_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id', 'id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    public function sourceBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'source_batch_id', 'id');
    }

    public function destinationBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'destination_batch_id', 'id');
    }
}
