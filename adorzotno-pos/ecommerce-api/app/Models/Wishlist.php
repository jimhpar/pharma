<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Wishlist extends Model
{
    protected $table = 'wishlists';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = ['customer_id', 'sku_id'];

    /**
     * Get the customer that owns this wishlist item
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Get the SKU in this wishlist item
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * Convenience relation for the underlying product
     */
    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            Sku::class,
            'id',
            'id',
            'sku_id',
            'product_id'
        );
    }
}
