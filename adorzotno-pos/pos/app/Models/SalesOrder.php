<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    public const CHANNEL_POS = 'pos';
    public const CHANNEL_ECOMMERCE = 'online';
    public const CHANNEL_MANUAL = 'manual';
    public const REPORTABLE_SALES_STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'packed',
        'shipped',
        'delivered',
        'completed',
    ];

    protected $table = "sales_orders";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'customer_id',
        'cashier_id',
        'shift_id',
        'sales_channel',
        'order_no',
        'invoice_no',
        'order_date',
        'status',
        'payment_status',
        'fulfillment_status',
        'sub_total',
        'item_discount_total',
        'cart_discount_total',
        'tax_total',
        'shipping_fee',
        'other_charge_total',
        'grand_total',
        'paid_total',
        'due_total',
        'change_amount',
        'customer_note',
        'internal_note',
        'earned_points',
        'redeemed_points',
        'point_discount_amount',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'customer_id' => 'integer',
        'cashier_id' => 'integer',
        'shift_id' => 'integer',
        'order_date' => 'datetime',
        'sub_total' => 'decimal:2',
        'item_discount_total' => 'decimal:2',
        'cart_discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'other_charge_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'due_total' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'earned_points' => 'integer',
        'redeemed_points' => 'integer',
        'point_discount_amount' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id', 'id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_order_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'sales_order_id', 'id');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'order_coupon_usages', 'sales_order_id', 'coupon_id')
            ->withPivot('discount_amount');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'sales_order_id', 'id')->latestOfMany();
    }

    public function getSaleTypeAttribute(): string
    {
        return $this->extractInternalNoteMeta()['sale type'] ?? 'Sale';
    }

    public function getSalesChannelLabelAttribute(): string
    {
        $canonicalChannel = self::canonicalChannel($this->sales_channel);

        return self::channelOptions()[$canonicalChannel] ?? $this->formatEnumLabel($this->sales_channel);
    }

    public static function channelOptions(): array
    {
        return [
            self::CHANNEL_POS => 'POS',
            self::CHANNEL_ECOMMERCE => 'E-commerce',
            self::CHANNEL_MANUAL => 'Manual',
        ];
    }

    public static function channelFilterValues(?string $channel): array
    {
        $normalized = strtolower(trim((string) $channel));

        return match ($normalized) {
            self::CHANNEL_ECOMMERCE, 'ecommerce', 'e-commerce', 'web', 'website' => [
                self::CHANNEL_ECOMMERCE,
                'Online',
                'ONLINE',
                'ecommerce',
                'Ecommerce',
                'e-commerce',
                'E-commerce',
                'E-Commerce',
                'web',
                'website',
            ],
            self::CHANNEL_POS => [
                self::CHANNEL_POS,
                'POS',
                'Pos',
            ],
            self::CHANNEL_MANUAL => [
                self::CHANNEL_MANUAL,
                'Manual',
                'MANUAL',
            ],
            default => $normalized === '' ? [] : [$channel],
        };
    }

    public static function canonicalChannel(?string $channel): ?string
    {
        $normalized = strtolower(trim((string) $channel));

        return match ($normalized) {
            self::CHANNEL_ECOMMERCE, 'ecommerce', 'e-commerce', 'web', 'website' => self::CHANNEL_ECOMMERCE,
            self::CHANNEL_POS => self::CHANNEL_POS,
            self::CHANNEL_MANUAL => self::CHANNEL_MANUAL,
            default => $normalized === '' ? null : $channel,
        };
    }

    public function getPaymentMethodAttribute(): string
    {
        if ($this->relationLoaded('payments') && $this->payments->isNotEmpty()) {
            return $this->formatPaymentMethodSummary($this->payments->pluck('payment_method')->all());
        }

        $latestMethod = $this->relationLoaded('latestPayment')
            ? $this->latestPayment?->payment_method_label
            : null;

        if ($latestMethod) {
            return $latestMethod;
        }

        return $this->extractInternalNoteMeta()['payment method'] ?? 'N/A';
    }

    public function getPaymentMethodSummaryAttribute(): string
    {
        return $this->payment_method;
    }

    public function getShipmentZoneNameAttribute(): ?string
    {
        return $this->extractInternalNoteMeta()['shipment zone'] ?? null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->formatEnumLabel($this->status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->formatEnumLabel($this->payment_status);
    }

    public function getFulfillmentStatusLabelAttribute(): string
    {
        return $this->formatEnumLabel($this->fulfillment_status);
    }

    private function extractInternalNoteMeta(): array
    {
        $meta = [];

        foreach (explode('|', (string) $this->internal_note) as $segment) {
            $parts = array_map('trim', explode(':', $segment, 2));

            if (count($parts) !== 2 || $parts[0] === '') {
                continue;
            }

            $meta[strtolower($parts[0])] = $parts[1];
        }

        return $meta;
    }

    private function formatEnumLabel(?string $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return 'N/A';
        }

        return ucwords(str_replace('_', ' ', $normalized));
    }

    private function formatPaymentMethodSummary(array $methods): string
    {
        $labels = collect($methods)
            ->filter(fn ($method) => trim((string) $method) !== '')
            ->map(fn ($method) => Payment::labelForMethod((string) $method))
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return 'N/A';
        }

        if ($labels->count() === 1) {
            return $labels->first();
        }

        return 'Mixed (' . $labels->implode(' + ') . ')';
    }
}
