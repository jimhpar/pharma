<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = "product_images";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'sku_id',
        'image_path',
        'alt_text',
        'sort_order',
    ];

    public function getImageAttribute(): ?string
    {
        return $this->attributes['image_path'] ?? null;
    }

    public function setImageAttribute(?string $value): void
    {
        $this->attributes['image_path'] = $value;
    }
}
