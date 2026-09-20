<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Batch extends Model
{
    use HasFactory;
    protected $table = "inventory_batches";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'sku_id',       
        'supplier_id',  
        'purchase_price', 
        'quantity', 
        'current_quantity',
    ];

    public function sku(): HasOne
    {
        return $this->hasOne(Sku::class, 'id', 'sku_id');
    }
}
