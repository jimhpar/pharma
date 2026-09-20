<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percent';

    protected $table = 'coupons';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function isCurrentlyValid(): bool
    {
        if (strtolower((string) $this->status) !== 'active') {
            return false;
        }

        if ($this->start_at !== null && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at !== null && $this->end_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $totalAmount): float
    {
        $discount = match (strtolower((string) $this->discount_type)) {
            self::TYPE_PERCENTAGE => $totalAmount * ((float) $this->discount_value / 100),
            default => (float) $this->discount_value,
        };

        if ($this->max_discount_amount !== null) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        return round(max(0, min($discount, $totalAmount)), 2);
    }

    public function getTypeAttribute(): string
    {
        return (string) $this->discount_type;
    }

    public function getValueAttribute(): float
    {
        return (float) $this->discount_value;
    }
}
