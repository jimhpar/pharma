<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function show()
    {
        return view('user.index');
    }

    public function list(Request $request)
    {
        $users = User::query()
            ->with(['branchRoles.branch', 'branchRoles.role', 'salesStaffProfile'])
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->orderByDesc('id');

        return DataTables()->of($users)
            ->addColumn('phone_display', fn (User $user) => $user->phone ?? 'N/A')
            ->addColumn('role_summary', function (User $user) {
                if ($user->branchRoles->isEmpty()) {
                    return 'N/A';
                }

                return $user->branchRoles
                    ->map(function ($assignment) {
                        $roleName = $assignment->role?->name ?? 'No Role';
                        $branchName = $assignment->branch?->name ?? 'All Branches';

                        return $roleName . ' @ ' . $branchName;
                    })
                    ->implode('<br>');
            })
            ->addColumn('staff_badge', function (User $user) {
                if ($user->salesStaffProfile?->is_active) {
                    return '<label class="btn btn-info">Staff</label>';
                }

                return '<label class="btn btn-secondary">No</label>';
            })
            ->addColumn('status_badge', function (User $user) {
                return match ($user->status) {
                    'active' => '<label class="btn btn-success">Active</label>',
                    'inactive' => '<label class="btn btn-secondary">Inactive</label>',
                    default => '<label class="btn btn-warning">Suspended</label>',
                };
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['role_summary', 'staff_badge', 'status_badge'])
            ->make(true);
    }

    public function create()
    {
        return view('user.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        return redirect()->route('user.show')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user = User::query()->findOrFail($id);

        return view('user.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        Session::flash('success', 'User updated successfully.');

        return redirect()->route('user.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $user = User::query()
            ->with(['customer', 'salesStaffProfile', 'cashierShifts', 'salesOrders', 'orders'])
            ->find($request->id);

        if ($user === null) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (
            $user->customer !== null
            || $user->salesStaffProfile !== null
            || $user->cashierShifts->isNotEmpty()
            || $user->salesOrders->isNotEmpty()
            || $user->orders->isNotEmpty()
        ) {
            return response()->json([
                'message' => 'This user has related business data and cannot be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($user) {
            $user->branchRoles()->delete();
            $user->delete();
        });

        return response()->json(['success' => 'User deleted successfully.']);
    }
}
