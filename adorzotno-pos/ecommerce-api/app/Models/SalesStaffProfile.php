<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesStaffProfile extends Model
{
    protected $table = 'sales_staff_profiles';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'employee_code',
        'default_branch_id',
        'commission_plan_id',
        'is_salesperson',
        'is_active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'default_branch_id' => 'integer',
        'commission_plan_id' => 'integer',
        'is_salesperson' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id', 'id');
    }

    public function commissionPlan(): BelongsTo
    {
        return $this->belongsTo(SalesCommissionPlan::class, 'commission_plan_id', 'id');
    }
}
