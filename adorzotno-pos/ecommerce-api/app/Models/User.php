<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'status',
        'last_login_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'user_id', 'id');
    }

    public function branchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class, 'user_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_branch_roles', 'user_id', 'role_id')
            ->withPivot(['id', 'branch_id', 'created_at']);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branch_roles', 'user_id', 'branch_id')
            ->withPivot(['id', 'role_id', 'created_at'])
            ->distinct();
    }

    public function salesStaffProfile(): HasOne
    {
        return $this->hasOne(SalesStaffProfile::class, 'user_id', 'id');
    }

    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class, 'cashier_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'cashier_id', 'id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'cashier_id', 'id');
    }

    public function approvedSalesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'approved_by', 'id');
    }

    public function processedSalesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'processed_by', 'id');
    }

    public function hasRoleSlug(string|array $slugs, ?int $branchId = null): bool
    {
        $slugs = Arr::wrap($slugs);

        $query = $this->branchRoles()->whereHas('role', function ($roleQuery) use ($slugs) {
            $roleQuery->whereIn('slug', $slugs);
        });

        if ($branchId !== null) {
            $query->where(function ($branchQuery) use ($branchId) {
                $branchQuery->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->exists();
    }

    public function hasPermissionSlug(string|array $slugs, ?int $branchId = null): bool
    {
        $slugs = Arr::wrap($slugs);

        $query = $this->branchRoles()->whereHas('role.permissions', function ($permissionQuery) use ($slugs) {
            $permissionQuery->whereIn('slug', $slugs);
        });

        if ($branchId !== null) {
            $query->where(function ($branchQuery) use ($branchId) {
                $branchQuery->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->exists();
    }
}
