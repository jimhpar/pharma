<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variation extends Model
{
    protected $table = "variations";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'type',
        'value',      
    ];
}
