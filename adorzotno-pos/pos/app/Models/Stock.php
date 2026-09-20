<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = "stocks";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'order_id',  
        'product_id',     
        'sku_id', 
        'type',
        'identifier',
        'quantity',
        'batch_id',     
    ];
}
