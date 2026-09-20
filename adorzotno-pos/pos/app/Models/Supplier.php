<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = "suppliers";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'supplier_code',
        'name',
        'email', 
        'phone', 
        'address',
        'payment_terms',
        'opening_balance',
        'current_due',
        'credit_limit',
        'status',    
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_due' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'id');
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'supplier_id', 'id');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class, 'supplier_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'supplier_id', 'id');
    }

    public function supplierLedgers(): HasMany
    {
        return $this->hasMany(SupplierLedger::class, 'supplier_id', 'id');
    }
}
