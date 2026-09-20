<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPrescription extends Model
{
    protected $table = 'customer_prescriptions';

    protected $fillable = [
        'customer_id',
        'title',
        'notes',
        'image_path',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? url($this->image_path) : null;
    }
}
