<?php

namespace App\Http\Controllers;

use App\Models\SalesCommissionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class SalesCommissionPlanController extends Controller
{
    public function show()
    {
        return view('sales_commission_plan.index');
    }

    public function list(Request $request)
    {
        $plans = SalesCommissionPlan::query()
            ->withCount('salesStaffProfiles')
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->orderByDesc('id');

        return DataTables()->of($plans)
            ->addColumn('rate_display', function (SalesCommissionPlan $plan) {
                $suffix = $plan->calculation_type === 'percentage' ? '%' : '';

                return number_format((float) $plan->rate, 2) . $suffix;
            })
            ->addColumn('status_badge', function (SalesCommissionPlan $plan) {
                if ($plan->is_active) {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->addColumn('staff_count', fn (SalesCommissionPlan $plan) => (int) $plan->sales_staff_profiles_count)
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        return view('sales_commission_plan.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        SalesCommissionPlan::query()->create($validated);

        return redirect()->route('salesCommissionPlan.show')->with('success', 'Sales commission plan created successfully.');
    }

    public function edit($id)
    {
        $salesCommissionPlan = SalesCommissionPlan::query()->findOrFail($id);

        return view('sales_commission_plan.edit', compact('salesCommissionPlan'));
    }

    public function update(Request $request, $id)
    {
        $salesCommissionPlan = SalesCommissionPlan::query()->findOrFail($id);
        $validated = $this->validatePlan($request, $salesCommissionPlan->id);

        $salesCommissionPlan->update($validated);

        Session::flash('success', 'Sales commission plan updated successfully.');

        return redirect()->route('salesCommissionPlan.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $salesCommissionPlan = SalesCommissionPlan::query()
            ->withCount('salesStaffProfiles')
            ->find($request->id);

        if ($salesCommissionPlan === null) {
            return response()->json(['message' => 'Sales commission plan not found.'], 404);
        }

        if ((int) $salesCommissionPlan->sales_staff_profiles_count > 0) {
            return response()->json([
                'message' => 'This commission plan is assigned to staff and cannot be deleted.',
            ], 422);
        }

        $salesCommissionPlan->delete();

        return response()->json(['success' => 'Sales commission plan deleted successfully.']);
    }

    private function validatePlan(Request $request, ?int $planId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('sales_commission_plans', 'name')->ignore($planId)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('sales_commission_plans', 'code')->ignore($planId)],
            'calculation_type' => ['required', 'in:percentage,fixed'],
            'rate' => ['required', 'numeric', 'min:0'],
            'base_amount_type' => ['required', 'in:gross_sale,net_sale,profit'],
            'apply_scope' => ['required', 'in:invoice,item'],
            'min_target_amount' => ['nullable', 'numeric', 'min:0'],
            'max_commission_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        return [
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'calculation_type' => $validated['calculation_type'],
            'rate' => $validated['rate'],
            'base_amount_type' => $validated['base_amount_type'],
            'apply_scope' => $validated['apply_scope'],
            'min_target_amount' => $validated['min_target_amount'] ?? null,
            'max_commission_amount' => $validated['max_commission_amount'] ?? null,
            'is_active' => $validated['status'] === 'active',
        ];
    }
}
