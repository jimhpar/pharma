<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const TYPE_STANDARD = 'standard';
    public const TYPE_VARIANT_PARENT = 'variant_parent';
    public const TYPE_COMBO = 'combo';
    public const TYPE_SERVICE = 'service';

    protected $table = "products";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'brand_id',
        'unit_id',
        'tax_rule_id',
        'name',
        'mrp',
        'sale_price',
        'default_discount_type',
        'default_discount_value',
        'dosage_form',
        'strength',
        'coating_type',
        'generic_name',
        'manufacturer_name',
        'source_external_id',
        'slug', 
        'sku',
        'product_type',
        'type',
        'short_description',
        'base_price',
        'stock_quantity',
        'description',
        'long_description',
        'thumbnail_image', 
        'status',
        'is_featured',
        'is_popular',
        'is_online_enabled',
        'is_pos_enabled',
        'seo_title',
        'meta_title',
        'seo_description',
        'meta_description',
        'created_by',
    ];

    public function sku(): HasMany
    {
        return $this->hasMany(Sku::class, 'product_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function productWarnings(): HasMany
    {
        return $this->hasMany(ProductWarning::class, 'product_id', 'id');
    }

    public function categoryPromotions(): HasMany
    {
        return $this->hasMany(CategoryPromotion::class, 'category_id', 'category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id', 'id');
    }

    public function getTypeAttribute(): ?string
    {
        return match ($this->attributes['product_type'] ?? null) {
            self::TYPE_VARIANT_PARENT => 'Variation',
            self::TYPE_STANDARD => 'Single',
            self::TYPE_COMBO => 'Combo',
            self::TYPE_SERVICE => 'Service',
            default => $this->attributes['product_type'] ?? null,
        };
    }

    public function setTypeAttribute(?string $value): void
    {
        $this->attributes['product_type'] = match ($value) {
            'Variation' => self::TYPE_VARIANT_PARENT,
            'Single' => self::TYPE_STANDARD,
            'Combo' => self::TYPE_COMBO,
            'Service' => self::TYPE_SERVICE,
            default => $value,
        };
    }

    public function getTypeLabelAttribute(): ?string
    {
        return match ($this->attributes['product_type'] ?? null) {
            self::TYPE_STANDARD => 'Standard',
            self::TYPE_VARIANT_PARENT => 'Variant',
            self::TYPE_COMBO => 'Combo',
            self::TYPE_SERVICE => 'Service',
            default => null,
        };
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['long_description'] ?? null;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['long_description'] = $value;
    }

    public function getMetaTitleAttribute(): ?string
    {
        return $this->attributes['seo_title'] ?? null;
    }

    public function setMetaTitleAttribute(?string $value): void
    {
        $this->attributes['seo_title'] = $value;
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return $this->attributes['seo_description'] ?? null;
    }

    public function setMetaDescriptionAttribute(?string $value): void
    {
        $this->attributes['seo_description'] = $value;
    }

    public function getSellingPriceAttribute(): float
    {
        return (float) (
            $this->attributes['sale_price']
            ?? $this->attributes['base_price']
            ?? 0
        );
    }

    public function getQuantityAttribute(): int
    {
        return (int) ($this->attributes['stock_quantity'] ?? 0);
    }
}
