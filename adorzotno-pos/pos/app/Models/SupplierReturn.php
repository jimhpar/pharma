<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReturn extends Model
{
    use HasFactory;

    protected $table = 'supplier_returns';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'return_no',
        'return_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'supplier_id' => 'integer',
        'return_date' => 'date',
        'created_by' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class, 'supplier_return_id', 'id');
    }
}
