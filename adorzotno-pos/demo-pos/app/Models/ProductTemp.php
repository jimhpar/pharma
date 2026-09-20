<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTemp extends Model
{
    protected $table = "product_temp";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'variation_ids',
        'variation_product_images',
        'variation_sku_code',
        'variation_barcode',
        'variation_product_code',
        'variation_cost_price',
        'variation_retail_price',
        'variation_wholesale_price',
        'minimum_selling_price',
        'online_price',
        'weight',
        'variation_units_per_strip',
        'variation_medicine_unit_price',
        'rating',
        'dosage_details',
        'variation_discount_type',  
        'variation_discount_amount',
        'variation_specifications',
        'variation_additional_description',
        'variation_stock_alert',
        'session_id',         
    ];

    protected $casts = [
        'variation_ids' => 'json',
        'variation_product_images' => 'json',
        'variation_units_per_strip' => 'integer',
        'variation_medicine_unit_price' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the variations for this product temp entry
     * Returns Variation models based on variation_ids JSON array
     */
    public function variations()
    {
        return Variation::whereIn('id', $this->variation_ids ?? [])->get();
    }

    /**
     * Get variation values as comma-separated string
     * Returns the actual variation value names for display
     */
    public function getVariationValuesAttribute()
    {
        $values = $this->variations()->pluck('value')->toArray();
        return implode(', ', $values);
    }

    public function getVariationBasePriceAttribute(): ?string
    {
        return $this->attributes['variation_retail_price'] ?? null;
    }

    public function setVariationBasePriceAttribute($value): void
    {
        $this->attributes['variation_retail_price'] = $value;
    }

}
