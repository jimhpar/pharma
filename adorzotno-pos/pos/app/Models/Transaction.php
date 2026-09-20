<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = "transactions";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'order_id',
        'amount',
        'payment_type',
        'payment_method',
        'note',
    ];

    public function order()
    {
        return $this->hasOne(SalesOrder::class, 'id', 'order_id');
    }
}
