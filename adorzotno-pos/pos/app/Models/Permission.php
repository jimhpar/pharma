<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = "permissions";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'module',
        'action',
        'slug', 
        'description', 
        'created_at',
    ];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'permission_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withPivot(['id', 'created_at']);
    }
}
