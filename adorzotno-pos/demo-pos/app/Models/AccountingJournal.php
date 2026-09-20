<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingJournal extends Model
{
    protected $table = "accounting_journals";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'journal_date',
        'reference_type',
        'reference_id',
        'memo',    
        'posted_by',
        'created_at'
    ];

    protected $casts = [
        'journal_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class, 'journal_id', 'id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by', 'id');
    }
}
