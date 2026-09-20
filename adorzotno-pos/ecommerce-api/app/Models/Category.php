<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = "categories";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'sort_order',
        'icon',
        'image',
        'is_featured',
        'is_popular',
        'is_top_deal',
        'status',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(CategoryPromotion::class, 'category_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
