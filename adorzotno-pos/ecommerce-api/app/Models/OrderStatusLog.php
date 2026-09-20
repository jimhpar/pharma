<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderStatusLog extends Model
{
    protected $table = "order_status_log";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'order_id',
        'status', 
        'added_by',     
    ];

    public function addedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
}
