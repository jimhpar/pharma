<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = "roles";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withPivot(['id', 'created_at']);
    }

    public function userBranchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class, 'role_id', 'id');
    }
}
