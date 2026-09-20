<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterBranchTransfer extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'inter_branch_transfers';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public const UPDATED_AT = null;

    protected $fillable = [
        'transfer_no',
        'from_branch_id',
        'from_warehouse_id',
        'to_branch_id',
        'to_warehouse_id',
        'requested_by',
        'approved_by',
        'received_by',
        'status',
        'requested_at',
        'approved_at',
        'dispatched_at',
        'received_at',
        'discrepancy_note',
        'created_at',
    ];

    protected $casts = [
        'from_branch_id' => 'integer',
        'from_warehouse_id' => 'integer',
        'to_branch_id' => 'integer',
        'to_warehouse_id' => 'integer',
        'requested_by' => 'integer',
        'approved_by' => 'integer',
        'received_by' => 'integer',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InterBranchTransferItem::class, 'transfer_id', 'id');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->status));
    }
}
