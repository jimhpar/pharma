<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $table = 'customer_groups';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'discount_percent',
        'created_at',
      
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'customer_group_id', 'id');
    }
}
