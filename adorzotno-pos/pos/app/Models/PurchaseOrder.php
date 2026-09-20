<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELED = 'canceled';

    protected $table = "purchase_orders";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'purchase_no',
        'invoice_no',
        'purchase_date',
        'status',
        'sub_total',
        'discount_total',
        'tax_total',
        'other_charge_total',
        'grand_total',
        'paid_total',
        'due_total',
        'note',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'supplier_id' => 'integer',
        'purchase_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'other_charge_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'due_total' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
