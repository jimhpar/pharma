<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function handle(Request $request, Closure $next, string ...$slugs): Response
    {
        $user = $request->user();

        if ($user === null || $user->hasRoleSlug('super-admin')) {
            return $next($request);
        }

        $slugs = array_values(array_filter($slugs));

        if ($slugs === []) {
            return $next($request);
        }

        $existingPermissionSlugs = Permission::query()
            ->whereIn('slug', $slugs)
            ->pluck('slug')
            ->all();

        if ($existingPermissionSlugs === []) {
            return $next($request);
        }

        $branchId = $this->branchContext->currentBranchId($user);
        $roleIds = $user->branchRoles()
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where(function ($roleQuery) use ($branchId) {
                    $roleQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->pluck('role_id')
            ->filter()
            ->unique();

        if ($roleIds->isEmpty()) {
            return $next($request);
        }

        $roleHasAnyPermissions = RolePermission::query()
            ->whereIn('role_id', $roleIds)
            ->exists();

        if (!$roleHasAnyPermissions) {
            return $next($request);
        }

        foreach ($existingPermissionSlugs as $slug) {
            if ($user->hasPermissionSlug($slug, $branchId)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this module in the selected branch.');
    }
}
