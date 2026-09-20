<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Role;
use App\Models\SalesCommissionPlan;
use App\Models\SalesStaffProfile;
use App\Models\User;
use App\Models\UserBranchRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function show()
    {
        $commissionPlans = SalesCommissionPlan::where('is_active', true)->orderBy('name')->get();
        return view('staff.index', compact('commissionPlans'));
    }

    public function list()
    {
        $profiles = SalesStaffProfile::query()
            ->with([
                'user.branchRoles.branch',
                'user.branchRoles.role',
                'defaultBranch',
                'commissionPlan',
            ])
            ->orderByDesc('id');

        return DataTables()->of($profiles)
            ->addColumn('staff_name',         fn (SalesStaffProfile $p) => $p->user?->name ?? 'N/A')
            ->addColumn('email',              fn (SalesStaffProfile $p) => $p->user?->email ?? 'N/A')
            ->addColumn('phone',              fn (SalesStaffProfile $p) => $p->user?->phone ?? 'N/A')
            ->addColumn('default_branch',     fn (SalesStaffProfile $p) => $p->defaultBranch?->name ?? 'N/A')
            ->addColumn('commission_plan',    fn (SalesStaffProfile $p) => $p->commissionPlan?->name ?? 'No Plan')
            ->addColumn('commission_plan_id', fn (SalesStaffProfile $p) => $p->commission_plan_id ?? 0)
            ->addColumn('commission_rate',    fn (SalesStaffProfile $p) => $p->commissionPlan
                ? ($p->commissionPlan->calculation_type === 'percentage'
                    ? number_format((float)$p->commissionPlan->rate, 2) . '%'
                    : '৳' . number_format((float)$p->commissionPlan->rate, 2) . ' fixed')
                : '—'
            )
            ->addColumn('role_summary', function (SalesStaffProfile $profile) {
                $roles = $profile->user?->branchRoles ?? collect();

                if ($roles->isEmpty()) {
                    return 'N/A';
                }

                return $roles->map(function ($assignment) {
                    $roleName = $assignment->role?->name ?? 'No Role';
                    $branchName = $assignment->branch?->name ?? 'All Branches';

                    return $roleName . ' @ ' . $branchName;
                })->implode('<br>');
            })
            ->addColumn('salesperson_badge', function (SalesStaffProfile $profile) {
                if ($profile->is_salesperson) {
                    return '<label class="btn btn-primary">Sales</label>';
                }

                return '<label class="btn btn-secondary">Support</label>';
            })
            ->addColumn('status_badge', function (SalesStaffProfile $profile) {
                if ($profile->is_active) {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['role_summary', 'salesperson_badge', 'status_badge'])
            ->make(true);
    }

    public function create()
    {
        $users = User::query()
            ->whereDoesntHave('salesStaffProfile')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get();
        $commissionPlans = SalesCommissionPlan::query()->where('is_active', true)->orderBy('name')->get();
        $assignmentRows = old('role_assignments', [['branch_id' => '', 'role_id' => '']]);
        $assignmentTemplate = view('staff._assignment_row', [
            'index' => '__INDEX__',
            'assignment' => ['branch_id' => '', 'role_id' => ''],
            'branches' => $branches,
            'roles' => $roles,
        ])->render();

        return view('staff.create', compact('users', 'branches', 'roles', 'commissionPlans', 'assignmentRows', 'assignmentTemplate'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateStaff($request);

        DB::transaction(function () use ($validated) {
            $profile = SalesStaffProfile::query()->create([
                'user_id' => $validated['user_id'],
                'employee_code' => $validated['employee_code'],
                'default_branch_id' => $validated['default_branch_id'],
                'commission_plan_id' => $validated['commission_plan_id'],
                'is_salesperson' => $validated['is_salesperson'],
                'is_active' => $validated['is_active'],
            ]);

            $this->syncRoleAssignments($profile->user_id, $validated['role_assignments']);
        });

        return redirect()->route('staff.show')->with('success', 'Staff profile created successfully.');
    }

    public function edit($id)
    {
        $staff = SalesStaffProfile::query()
            ->with(['user.branchRoles', 'defaultBranch', 'commissionPlan'])
            ->findOrFail($id);

        $users = User::query()
            ->where(function ($query) use ($staff) {
                $query->whereDoesntHave('salesStaffProfile')
                    ->orWhere('id', $staff->user_id);
            })
            ->orderBy('name')
            ->get();

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get();
        $commissionPlans = SalesCommissionPlan::query()->where('is_active', true)->orderBy('name')->get();
        $assignmentRows = old('role_assignments', $staff->user->branchRoles->map(function ($assignment) {
            return [
                'branch_id' => $assignment->branch_id,
                'role_id' => $assignment->role_id,
            ];
        })->values()->all());

        if (empty($assignmentRows)) {
            $assignmentRows = [['branch_id' => '', 'role_id' => '']];
        }

        $assignmentTemplate = view('staff._assignment_row', [
            'index' => '__INDEX__',
            'assignment' => ['branch_id' => '', 'role_id' => ''],
            'branches' => $branches,
            'roles' => $roles,
        ])->render();

        return view('staff.edit', compact('staff', 'users', 'branches', 'roles', 'commissionPlans', 'assignmentRows', 'assignmentTemplate'));
    }

    public function update(Request $request, $id)
    {
        $staff = SalesStaffProfile::query()->findOrFail($id);
        $validated = $this->validateStaff($request, $staff->id);

        DB::transaction(function () use ($staff, $validated) {
            $staff->update([
                'user_id' => $validated['user_id'],
                'employee_code' => $validated['employee_code'],
                'default_branch_id' => $validated['default_branch_id'],
                'commission_plan_id' => $validated['commission_plan_id'],
                'is_salesperson' => $validated['is_salesperson'],
                'is_active' => $validated['is_active'],
            ]);

            $this->syncRoleAssignments($validated['user_id'], $validated['role_assignments']);
        });

        Session::flash('success', 'Staff profile updated successfully.');

        return redirect()->route('staff.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $staff = SalesStaffProfile::query()
            ->with(['user.cashierShifts', 'user.salesOrders'])
            ->find($request->id);

        if ($staff === null) {
            return response()->json(['message' => 'Staff profile not found.'], 404);
        }

        if ($staff->user?->cashierShifts->isNotEmpty() || $staff->user?->salesOrders->isNotEmpty()) {
            return response()->json([
                'message' => 'This staff profile has transaction history and cannot be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($staff) {
            UserBranchRole::query()->where('user_id', $staff->user_id)->delete();
            $staff->delete();
        });

        return response()->json(['success' => 'Staff profile deleted successfully.']);
    }

    public function updateCommission(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id'           => ['required', 'integer', 'exists:sales_staff_profiles,id'],
            'commission_plan_id' => ['nullable', 'integer', 'exists:sales_commission_plans,id'],
        ]);

        $profile = SalesStaffProfile::findOrFail($request->staff_id);
        $profile->commission_plan_id = $request->commission_plan_id ?: null;
        $profile->save();

        $plan = $profile->commission_plan_id
            ? SalesCommissionPlan::find($profile->commission_plan_id)
            : null;

        return response()->json([
            'success'   => true,
            'plan_name' => $plan?->name ?? 'No Plan',
            'rate'      => $plan
                ? ($plan->calculation_type === 'percentage'
                    ? number_format((float)$plan->rate, 2) . '%'
                    : '৳' . number_format((float)$plan->rate, 2) . ' fixed')
                : '—',
        ]);
    }

    private function validateStaff(Request $request, ?int $staffId = null): array
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('sales_staff_profiles', 'user_id')->ignore($staffId),
            ],
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('sales_staff_profiles', 'employee_code')->ignore($staffId),
            ],
            'default_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'commission_plan_id' => ['nullable', 'integer', 'exists:sales_commission_plans,id'],
            'is_salesperson' => ['required', 'in:yes,no'],
            'status' => ['required', 'in:active,inactive'],
            'role_assignments' => ['required', 'array', 'min:1'],
            'role_assignments.*.branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'role_assignments.*.role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        return [
            'user_id' => (int) $validated['user_id'],
            'employee_code' => $validated['employee_code'] ?? null,
            'default_branch_id' => !empty($validated['default_branch_id']) ? (int) $validated['default_branch_id'] : null,
            'commission_plan_id' => !empty($validated['commission_plan_id']) ? (int) $validated['commission_plan_id'] : null,
            'is_salesperson' => $validated['is_salesperson'] === 'yes',
            'is_active' => $validated['status'] === 'active',
            'role_assignments' => $this->normalizeRoleAssignments($validated['role_assignments']),
        ];
    }

    private function normalizeRoleAssignments(array $assignments): array
    {
        $normalized = collect($assignments)
            ->map(function ($assignment) {
                return [
                    'branch_id' => !empty($assignment['branch_id']) ? (int) $assignment['branch_id'] : null,
                    'role_id' => (int) $assignment['role_id'],
                ];
            })
            ->filter(fn (array $assignment) => $assignment['role_id'] > 0)
            ->unique(fn (array $assignment) => ($assignment['branch_id'] ?? 'all') . ':' . $assignment['role_id'])
            ->values()
            ->all();

        if (empty($normalized)) {
            throw ValidationException::withMessages([
                'role_assignments' => 'At least one role assignment is required.',
            ]);
        }

        return $normalized;
    }

    private function syncRoleAssignments(int $userId, array $assignments): void
    {
        UserBranchRole::query()->where('user_id', $userId)->delete();

        foreach ($assignments as $assignment) {
            UserBranchRole::query()->create([
                'user_id' => $userId,
                'branch_id' => $assignment['branch_id'],
                'role_id' => $assignment['role_id'],
                'created_at' => now(),
            ]);
        }
    }
}
