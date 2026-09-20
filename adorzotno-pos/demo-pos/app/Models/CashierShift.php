<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $table = "cashier_shifts";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'cashier_id',
        'opened_at',    
        'opening_cash',
        'closed_at',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'status',
        'note',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'cashier_id' => 'integer',
        'opened_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closed_at' => 'datetime',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id', 'id');
    }
}
