<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = "banners";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'title',
        'banner_url',
        'image',
        'banner_type',    
        'status',
    ];
}
