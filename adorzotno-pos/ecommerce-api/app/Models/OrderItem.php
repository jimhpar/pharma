<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = "order_items";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'discount_total',
        'line_total',
    ];

    public function order()
    {
        return $this->hasOne(OrderInfo::class, 'id', 'order_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
