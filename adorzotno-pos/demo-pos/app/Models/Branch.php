<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $table = "branches";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'name',       
        'code',  
        'address', 
        'phone', 
        'email',
        'invoice_prefix',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'branch_id', 'id');
    }

    public function defaultWarehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class, 'branch_id', 'id')->where('is_default', true);
    }

    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchPrice::class, 'branch_id', 'id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'branch_id', 'id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'branch_id', 'id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'branch_id', 'id');
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class, 'branch_id', 'id');
    }

    public function stockTransfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id', 'id');
    }

    public function stockTransfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id', 'id');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class, 'branch_id', 'id');
    }

    public function userBranchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class, 'branch_id', 'id');
    }

    public function salesStaffProfiles(): HasMany
    {
        return $this->hasMany(SalesStaffProfile::class, 'default_branch_id', 'id');
    }

    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class, 'branch_id', 'id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'branch_id', 'id');
    }

    public function outgoingInterBranchTransfers(): HasMany
    {
        return $this->hasMany(InterBranchTransfer::class, 'from_branch_id', 'id');
    }

    public function incomingInterBranchTransfers(): HasMany
    {
        return $this->hasMany(InterBranchTransfer::class, 'to_branch_id', 'id');
    }
}
