<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryPromotion extends Model
{
    protected $table = "category_promotion";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'title',
        'icon',
        'category_id',
        'status',
        'sort_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
