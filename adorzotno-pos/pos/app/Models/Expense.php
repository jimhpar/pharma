<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $table = 'expenses';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'branch_id',
        'expense_category_id',
        'account_id',
        'payment_source',
        'vendor_name',
        'expense_date',
        'amount',
        'note',
        'attachment_path',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'datetime',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id', 'id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
