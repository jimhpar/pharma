<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentZone extends Model
{
    protected $table = "shipping_zones";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = [
        'name',
        'charge',
        'status',
    ];
}
