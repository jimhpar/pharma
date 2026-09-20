<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BranchPrice extends Model
{
    use HasFactory;
    protected $table = "branch_prices";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'branch_id',       
        'sku_id',  
        'retail_price', 
        'wholesale_price', 
        'minimum_selling_price',
        'online_price',
        'is_active',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'sku_id' => 'integer',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'minimum_selling_price' => 'decimal:2',
        'online_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }
}
