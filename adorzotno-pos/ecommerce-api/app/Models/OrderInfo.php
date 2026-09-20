<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderInfo extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'billing_address_id',
        'shipping_address_id',
        'order_number',
        'status',
        'payment_status',
        'fulfillment_status',
        'currency',
        'sub_total',
        'discount_total',
        'tax_total',
        'shipping_total',
        'grand_total',
        'payment_method',
        'shipping_method',
        'customer_note',
        'admin_note',
        'placed_at',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'order_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function orderStatusLog()
    {
        return $this->hasOne(OrderStatusLog::class, 'order_id', 'id');
    }

    public function orderStatusLogs()
    {
        return $this->hasMany(OrderStatusLog::class, 'order_id', 'id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'order_id', 'id');
    }
}
