<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'customer_group_id',
        'name',
        'email',
        'phone',
        'billing_address',
        'shipping_address',
        'user_id',
        'customer_code',
        'opening_balance',
        'credit_limit',
        'current_due',
        'loyalty_points',
        'total_purchase_amount',
        'lifetime_earned_points',
        'lifetime_redeemed_points',
        'is_member',
        'membership_started_at',
        'status',
    ];

    protected $casts = [
        'customer_group_id'        => 'integer',
        'user_id'                  => 'integer',
        'opening_balance'          => 'decimal:2',
        'credit_limit'             => 'decimal:2',
        'current_due'              => 'decimal:2',
        'loyalty_points'           => 'integer',
        'total_purchase_amount'    => 'decimal:2',
        'lifetime_earned_points'   => 'integer',
        'lifetime_redeemed_points' => 'integer',
        'is_member'                => 'boolean',
        'membership_started_at'    => 'datetime',
    ];

    public function user():HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id', 'id');
    }

    public function shipmentZone(): BelongsTo
    {
        return $this->belongsTo(ShipmentZone::class, 'shipment_zone_id', 'id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'customer_id', 'id');
    }

    public function customerLedgers(): HasMany
    {
        return $this->hasMany(CustomerLedger::class, 'customer_id', 'id');
    }

    public function salesReturns(): HasManyThrough
    {
        return $this->hasManyThrough(
            SalesReturn::class,
            SalesOrder::class,
            'customer_id',
            'sales_order_id',
            'id',
            'id'
        );
    }

    public function loyaltyLedger(): HasMany
    {
        return $this->hasMany(LoyaltyPointLedger::class, 'customer_id', 'id');
    }

    public function getMemberStatusLabelAttribute(): string
    {
        return $this->is_member ? 'Member' : 'Regular';
    }
}
