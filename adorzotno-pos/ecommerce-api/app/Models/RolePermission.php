<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = "role_permissions";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission_id',
        'created_at',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'permission_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }
}
