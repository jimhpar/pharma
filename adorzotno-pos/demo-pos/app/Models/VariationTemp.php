<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationTemp extends Model
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
        'variation_discount_type',
        'variation_discount_amount',
        'variation_specifications',
        'variation_additional_description',
        'variation_stock_alert',
        'session_id',
    ];
 
}
