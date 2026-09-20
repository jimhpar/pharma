<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class BranchContext
{
    public const SESSION_KEY = 'current_branch_id';

    public function ensureInitialized(?User $user = null): ?Branch
    {
        $user ??= auth()->user();

        if (!$user instanceof User) {
            Session::forget(self::SESSION_KEY);

            return null;
        }

        $branches = $this->accessibleBranches($user);

        if ($branches->isEmpty()) {
            Session::forget(self::SESSION_KEY);

            return null;
        }

        $sessionBranchId = (int) Session::get(self::SESSION_KEY);
        $currentBranch = $branches->firstWhere('id', $sessionBranchId);

        if ($currentBranch instanceof Branch) {
            return $currentBranch;
        }

        $currentBranch = $branches->first();
        Session::put(self::SESSION_KEY, $currentBranch?->id);

        return $currentBranch;
    }

    public function currentBranch(?User $user = null): ?Branch
    {
        return $this->ensureInitialized($user);
    }

    public function currentBranchId(?User $user = null): ?int
    {
        return $this->currentBranch($user)?->id;
    }

    public function setCurrentBranch(User $user, int $branchId): Branch
    {
        $branch = $this->accessibleBranches($user)->firstWhere('id', $branchId);

        if (!$branch instanceof Branch) {
            throw new RuntimeException('You do not have access to the selected branch.');
        }

        Session::put(self::SESSION_KEY, $branch->id);

        return $branch;
    }

    public function accessibleBranches(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (!$user instanceof User) {
            return collect();
        }

        if ($this->hasCrossBranchAccess($user)) {
            return Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $assignedBranchIds = $user->branchRoles()
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id');

        if ($assignedBranchIds->isEmpty()) {
            return Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return Branch::query()
            ->where('is_active', true)
            ->whereIn('id', $assignedBranchIds)
            ->orderBy('name')
            ->get();
    }

    public function accessibleBranchIds(?User $user = null): array
    {
        return $this->accessibleBranches($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function accessibleWarehouses(?User $user = null): Collection
    {
        $branchIds = $this->accessibleBranchIds($user);

        if ($branchIds === []) {
            return collect();
        }

        return Warehouse::query()
            ->with('branch')
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function hasBranchAccess(?User $user, ?int $branchId): bool
    {
        if (!$user instanceof User || empty($branchId)) {
            return false;
        }

        if ($this->hasCrossBranchAccess($user)) {
            return Branch::query()
                ->where('id', $branchId)
                ->where('is_active', true)
                ->exists();
        }

        return in_array((int) $branchId, $this->accessibleBranchIds($user), true);
    }

    public function hasCrossBranchAccess(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->hasRoleSlug('super-admin')
            || $user->hasPermissionSlug('branches.cross_access');
    }

    public function scopeToCurrentBranch(Builder $query, string $column = 'branch_id', ?User $user = null): Builder
    {
        $branchId = $this->currentBranchId($user);

        if ($branchId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, $branchId);
    }

    public function resolveDefaultWarehouseForCurrentBranch(?User $user = null): ?Warehouse
    {
        $branchId = $this->currentBranchId($user);

        if ($branchId === null) {
            return null;
        }

        return Warehouse::query()
            ->with('branch')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? Warehouse::query()
                ->with('branch')
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
    }
}
