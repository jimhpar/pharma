<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = "accounts";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'account_name',
        'account_type',
        'account_code',
        'bank_name',
        'account_number',
        'bank_branch',
        'routing_number',
        'notes',
        'opening_balance',
        'current_balance',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class, 'account_id', 'id');
    }
}
