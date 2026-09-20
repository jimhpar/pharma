<?php

namespace App\Models;

use App\Models\Batch as ModelsBatch;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sku extends Model
{
    protected $table = "product_skus";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'product_id',
        'variant_parent_sku_id',
        'sku_code',
        'product_code',
        'barcode',
        'variant_name',
        'cost_price',
        'base_price',
        'retail_price',
        'wholesale_price',
        'minimum_selling_price',
        'online_price',
        'weight',
        'units_per_strip',
        'medicine_unit_price',
        'rating',
        'dosage_details',
        'track_stock',
        'track_batch',
        'track_expiry',
        'track_serial',
        'status',
        'sale_price',
        'discount_type',
        'discount_value',
        'discount_amount',
    ];

    protected $casts = [
        'units_per_strip' => 'integer',
        'medicine_unit_price' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ModelsBatch::class, 'id', 'sku_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'sku_id', 'id');
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'sku_id', 'id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'sku_id', 'id');
    }

    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchPrice::class, 'sku_id', 'id');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'sku_id', 'id');
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sku_id', 'id');
    }

    public function inventorySerials(): HasMany
    {
        return $this->hasMany(InventorySerial::class, 'sku_id', 'id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'sku_id', 'id');
    }

    public function inventoryAdjustmentItems(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class, 'sku_id', 'id');
    }

    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'sku_id', 'id');
    }

    public function supplierReturnItems(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class, 'sku_id', 'id');
    }

    public function variationRelation(): HasMany
    {
        return $this->hasMany(VariationRelation::class, 'sku_id', 'id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'sku_id', 'id');
    }

    public function getProductCodeAttribute(): ?string
    {
        return $this->attributes['sku_code'] ?? null;
    }

    public function setProductCodeAttribute(?string $value): void
    {
        $this->attributes['sku_code'] = $value;
    }

    public function getBasePriceAttribute(): ?string
    {
        return $this->attributes['retail_price'] ?? null;
    }

    public function setBasePriceAttribute($value): void
    {
        $this->attributes['retail_price'] = $value;
    }

    public function getSalePriceAttribute(): ?string
    {
        return $this->attributes['online_price']
            ?? $this->attributes['retail_price']
            ?? null;
    }

    public function setSalePriceAttribute($value): void
    {
        $this->attributes['online_price'] = $value;
    }

    public function getDisplayNameAttribute(): string
    {
        $productName = $this->product?->name ?? ('SKU #' . $this->id);
        $variantName = trim((string) ($this->variant_name ?? ''));

        // Append pharma attributes if present
        $pharma = array_filter([
            $this->product?->dosage_form,
            $this->product?->strength,
        ]);
        if (!empty($pharma)) {
            $productName .= ' (' . implode(' ', $pharma) . ')';
        }

        if ($variantName === '') {
            return $productName . ' [' . ($this->sku_code ?? 'N/A') . ']';
        }

        return $productName . ' - ' . $variantName . ' [' . ($this->sku_code ?? 'N/A') . ']';
    }

    public function getMedicineUnitPriceForPosAttribute(): float
    {
        $medicinePrice = $this->attributes['medicine_unit_price'] ?? null;

        if ($medicinePrice !== null && $medicinePrice !== '') {
            return (float) $medicinePrice;
        }

        $unitsPerStrip = max(1, (int) ($this->attributes['units_per_strip'] ?? 1));

        return round(((float) ($this->attributes['retail_price'] ?? 0)) / $unitsPerStrip, 2);
    }
}
