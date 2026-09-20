<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $table = "warehouses";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'branch_id',       
        'name',  
        'code', 
        'address', 
        'is_default',
        'allow_negative_stock',
        'is_active',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'is_default' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'warehouse_id', 'id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'warehouse_id', 'id');
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'warehouse_id', 'id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'warehouse_id', 'id');
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class, 'warehouse_id', 'id');
    }

    public function stockTransfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id', 'id');
    }

    public function stockTransfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id', 'id');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class, 'warehouse_id', 'id');
    }

    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class, 'warehouse_id', 'id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'warehouse_id', 'id');
    }

    public function outgoingInterBranchTransfers(): HasMany
    {
        return $this->hasMany(InterBranchTransfer::class, 'from_warehouse_id', 'id');
    }

    public function incomingInterBranchTransfers(): HasMany
    {
        return $this->hasMany(InterBranchTransfer::class, 'to_warehouse_id', 'id');
    }
}
