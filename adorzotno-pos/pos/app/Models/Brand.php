<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $table = "brands";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'background_image',
        'rating',
        'products_count',
        'reviews_count',
        'description',
        'founded_year',
        'headquarter_address',
        'employees_count',
        'is_verified',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'products_count' => 'integer',
        'reviews_count' => 'integer',
        'founded_year' => 'integer',
        'employees_count' => 'integer',
        'is_verified' => 'boolean',
    ];

    public function brandTags(): HasMany
    {
        return $this->hasMany(BrandTag::class, 'brand_id', 'id');
    }

    public function brandCertifications(): HasMany
    {
        return $this->hasMany(BrandCertification::class, 'brand_id', 'id');
    }
}
