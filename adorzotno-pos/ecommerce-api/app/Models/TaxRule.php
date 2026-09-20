<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    protected $table = "tax_rules";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = [
        'name',  
        'rate_percent',     
        'is_inclusive', 
        'status',
      
    ];
}
