<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariationRelation extends Model
{
    protected $table = "variation_relation";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'sku_id',
        'product_id',
        'variation_id',      
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variation_id', 'id');
    }
}
