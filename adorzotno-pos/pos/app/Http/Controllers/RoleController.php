<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function show()
    {
        return view('role.index');
    }

    public function list()
    {
        $roles = Role::query()
            ->withCount(['permissions', 'userBranchRoles'])
            ->orderBy('name')
            ->orderByDesc('id');

        return DataTables()->of($roles)
            ->addColumn('system_badge', function (Role $role) {
                if ($role->is_system) {
                    return '<label class="btn btn-info">System</label>';
                }

                return '<label class="btn btn-secondary">Custom</label>';
            })
            ->addColumn('permission_count', fn (Role $role) => (int) $role->permissions_count)
            ->addColumn('assignment_count', fn (Role $role) => (int) $role->user_branch_roles_count)
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['system_badge'])
            ->make(true);
    }

    public function create()
    {
        $permissions = Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module');

        return view('role.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);

        DB::transaction(function () use ($validated) {
            $role = Role::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'is_system' => $validated['is_system'],
            ]);

            $role->permissions()->sync($validated['permission_ids']);
        });

        return redirect()->route('role.show')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::query()->with('permissions:id')->findOrFail($id);
        $permissions = Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module');
        $selectedPermissionIds = $role->permissions->pluck('id')->all();

        return view('role.edit', compact('role', 'permissions', 'selectedPermissionIds'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::query()->findOrFail($id);
        $validated = $this->validateRole($request, $role->id);

        DB::transaction(function () use ($role, $validated) {
            $role->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'is_system' => $validated['is_system'],
            ]);

            $role->permissions()->sync($validated['permission_ids']);
        });

        Session::flash('success', 'Role updated successfully.');

        return redirect()->route('role.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $role = Role::query()->withCount('userBranchRoles')->find($request->id);

        if ($role === null) {
            return response()->json(['message' => 'Role not found.'], 404);
        }

        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 422);
        }

        if ((int) $role->user_branch_roles_count > 0) {
            return response()->json(['message' => 'This role is assigned to staff and cannot be deleted.'], 422);
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });

        return response()->json(['success' => 'Role deleted successfully.']);
    }

    private function validateRole(Request $request, ?int $roleId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($roleId)],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($roleId)],
            'description' => ['nullable', 'string'],
            'is_system' => ['required', 'in:yes,no'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => !empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => $validated['is_system'] === 'yes',
            'permission_ids' => array_values(array_unique($validated['permission_ids'] ?? [])),
        ];
    }
}
