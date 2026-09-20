<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBox extends Model
{
    protected $table = 'inventory_boxes';

    protected $fillable = [
        'carton_id',
        'sku_id',
        'box_code',
        'box_number',
        'units_received',
        'units_available',
        'units_sold',
        'status',
        'opened_at',
    ];

    protected $casts = [
        'box_number'      => 'integer',
        'units_received'  => 'integer',
        'units_available' => 'integer',
        'units_sold'      => 'integer',
        'opened_at'       => 'datetime',
    ];

    public function carton(): BelongsTo
    {
        return $this->belongsTo(InventoryCarton::class, 'carton_id', 'id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /** Deduct units from this box; returns actual amount deducted. */
    public function deduct(int $qty): int
    {
        $deduct = min($this->units_available, $qty);
        if ($deduct <= 0) {
            return 0;
        }

        if ($this->status === 'sealed') {
            $this->status    = 'open';
            $this->opened_at = now();
        }

        $this->units_available -= $deduct;
        $this->units_sold      += $deduct;

        if ($this->units_available === 0) {
            $this->status = 'empty';
        }

        $this->save();

        return $deduct;
    }

    /** Restore units to this box (on sale return). */
    public function restore(int $qty): void
    {
        $this->units_available += $qty;
        $this->units_sold      = max(0, $this->units_sold - $qty);
        if ($this->units_available > 0 && $this->status === 'empty') {
            $this->status = 'open';
        }
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sealed' => '#1d4ed8',
            'open'   => '#d97706',
            'empty'  => '#9ca3af',
            default  => '#6b7280',
        };
    }

    public function getStatusBgAttribute(): string
    {
        return match ($this->status) {
            'sealed' => '#dbeafe',
            'open'   => '#fef3c7',
            'empty'  => '#f3f4f6',
            default  => '#f9fafb',
        };
    }
}
