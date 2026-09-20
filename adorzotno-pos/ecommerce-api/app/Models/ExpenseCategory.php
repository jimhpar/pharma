<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'is_active',       
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id', 'id');
    }
}
