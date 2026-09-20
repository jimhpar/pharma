<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function show()
    {
        return view('permission.index');
    }

    public function list()
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('module')
            ->orderBy('action')
            ->orderByDesc('id');

        return DataTables()->of($permissions)
            ->addColumn('role_count', fn (Permission $permission) => (int) $permission->roles_count)
            ->setRowAttr([
                'align' => 'center',
            ])
            ->make(true);
    }

    public function create()
    {
        return view('permission.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePermission($request);

        Permission::query()->create($validated);

        return redirect()->route('permission.show')->with('success', 'Permission created successfully.');
    }

    public function edit($id)
    {
        $permission = Permission::query()->findOrFail($id);

        return view('permission.edit', compact('permission'));
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::query()->findOrFail($id);
        $validated = $this->validatePermission($request, $permission->id);

        $permission->update($validated);

        Session::flash('success', 'Permission updated successfully.');

        return redirect()->route('permission.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $permission = Permission::query()->withCount('rolePermissions')->find($request->id);

        if ($permission === null) {
            return response()->json(['message' => 'Permission not found.'], 404);
        }

        if ((int) $permission->role_permissions_count > 0) {
            return response()->json(['message' => 'This permission is assigned to roles and cannot be deleted.'], 422);
        }

        $permission->delete();

        return response()->json(['success' => 'Permission deleted successfully.']);
    }

    private function validatePermission(Request $request, ?int $permissionId = null): array
    {
        $validated = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('permissions', 'slug')->ignore($permissionId)],
            'description' => ['nullable', 'string'],
        ]);

        return [
            'module' => $validated['module'],
            'action' => $validated['action'],
            'slug' => !empty($validated['slug'])
                ? $validated['slug']
                : Str::slug($validated['module'] . '.' . $validated['action']),
            'description' => $validated['description'] ?? null,
        ];
    }
}
