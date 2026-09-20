<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderPerson extends Model
{
    protected $table = "sales_order_salespersons";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'sales_order_id',
        'salesperson_user_id',
        'commission_plan_id',
        'commission_basis_amount',    
        'commission_rate',
        'commission_amount',
        'payment_status',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

}
