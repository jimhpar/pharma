<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends Model
{
    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'requisition_no',
        'requisition_date',
        'status',
        'reason',
        'note',
        'rejection_note',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'approved_at'      => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeConverted(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
