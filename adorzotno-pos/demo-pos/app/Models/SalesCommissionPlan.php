<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SalesCommissionPlan extends Model
{
    protected $table = "sales_commission_plans";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'calculation_type',
        'rate',    
        'base_amount_type',
        'apply_scope',
        'min_target_amount',
        'max_commission_amount',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'min_target_amount' => 'decimal:2',
        'max_commission_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function salesStaffProfiles(): HasMany
    {
        return $this->hasMany(SalesStaffProfile::class, 'commission_plan_id', 'id');
    }
}
