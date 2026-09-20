<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'product_id',
        'rating',
        'title',
        'comment',
        'status',
        'is_verified_purchase',
        'approved_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'is_verified_purchase' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->customer();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
