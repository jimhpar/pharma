<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InterBranchTransfer;
use App\Models\Payment;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function show()
    {
        $branches = $this->branchContext()->accessibleBranches(auth()->user())
            ->sortBy('name')
            ->values();

        return view('branch.index', compact('branches'));
    }

    public function list(Request $request)
    {
        $branchIds = $this->branchContext()->accessibleBranchIds(auth()->user());
        $branches = Branch::query()
            ->with('defaultWarehouse')
            ->whereIn('id', $branchIds)
            ->when($request->filter_status !== null && $request->filter_status !== '', fn ($q) => $q->where('is_active', $request->filter_status === 'active'))
            ->orderBy('name')
            ->orderBy('id', 'desc');

        return DataTables()->of($branches)
            ->addColumn('default_warehouse', fn (Branch $branch) => $branch->defaultWarehouse?->name ?? 'N/A')
            ->addColumn('status', function (Branch $branch) {
                if ($branch->is_active) {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status'])
            ->make(true);
    }

    public function create()
    {
        return view('branch.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255|unique:branches,name',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'invoice_prefix' => 'required|string|max:20|unique:branches,invoice_prefix',
            'status' => 'required|in:active,inactive',
            'default_warehouse_name' => 'required|string|max:255',
            'default_warehouse_code' => 'required|string|max:50|unique:warehouses,code',
            'default_warehouse_address' => 'nullable|string',
            'default_allow_negative_stock' => 'required|in:yes,no',
        ]);

        DB::transaction(function () use ($validated) {
            $branch = Branch::query()->create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'invoice_prefix' => $validated['invoice_prefix'],
                'is_active' => $validated['status'] === 'active',
            ]);

            Warehouse::query()->create([
                'branch_id' => $branch->id,
                'name' => $validated['default_warehouse_name'],
                'code' => $validated['default_warehouse_code'],
                'address' => $validated['default_warehouse_address'] ?? null,
                'is_default' => true,
                'allow_negative_stock' => $validated['default_allow_negative_stock'] === 'yes',
                'is_active' => $validated['status'] === 'active',
            ]);
        });

        return redirect()->route('branch.show')->with('success', 'Branch created successfully.');
    }

    public function edit($id)
    {
        $branch = Branch::query()
            ->with(['warehouses' => fn ($query) => $query->orderBy('name'), 'defaultWarehouse'])
            ->whereIn('id', $this->branchContext()->accessibleBranchIds(auth()->user()))
            ->findOrFail($id);

        return view('branch.edit', compact('branch'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->ignore($id),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->ignore($id),
            ],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'invoice_prefix' => [
                'required',
                'string',
                'max:20',
                Rule::unique('branches', 'invoice_prefix')->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'new_default_warehouse_name' => ['nullable', 'string', 'max:255'],
            'new_default_warehouse_code' => ['nullable', 'string', 'max:50', Rule::unique('warehouses', 'code')],
            'new_default_warehouse_address' => ['nullable', 'string'],
            'new_default_allow_negative_stock' => ['nullable', 'in:yes,no'],
        ]);

        $branch = Branch::query()
            ->with('warehouses')
            ->whereIn('id', $this->branchContext()->accessibleBranchIds(auth()->user()))
            ->findOrFail($id);

        $newWarehouseRequested = filled($validated['new_default_warehouse_name'] ?? null)
            || filled($validated['new_default_warehouse_code'] ?? null);

        if ($newWarehouseRequested) {
            if (blank($validated['new_default_warehouse_name'] ?? null) || blank($validated['new_default_warehouse_code'] ?? null)) {
                throw ValidationException::withMessages([
                    'new_default_warehouse_name' => 'New default warehouse name and code are both required.',
                ]);
            }
        } elseif (empty($validated['default_warehouse_id'])) {
            throw ValidationException::withMessages([
                'default_warehouse_id' => 'Select a default warehouse or create a new one.',
            ]);
        }

        DB::transaction(function () use ($branch, $validated, $newWarehouseRequested) {
            $branch->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'invoice_prefix' => $validated['invoice_prefix'],
                'is_active' => $validated['status'] === 'active',
            ]);

            Warehouse::query()->where('branch_id', $branch->id)->update([
                'is_default' => false,
            ]);

            if ($newWarehouseRequested) {
                Warehouse::query()->create([
                    'branch_id' => $branch->id,
                    'name' => $validated['new_default_warehouse_name'],
                    'code' => $validated['new_default_warehouse_code'],
                    'address' => $validated['new_default_warehouse_address'] ?? null,
                    'is_default' => true,
                    'allow_negative_stock' => ($validated['new_default_allow_negative_stock'] ?? 'no') === 'yes',
                    'is_active' => true,
                ]);
            } else {
                $defaultWarehouse = Warehouse::query()
                    ->where('branch_id', $branch->id)
                    ->findOrFail((int) $validated['default_warehouse_id']);

                $defaultWarehouse->update(['is_default' => true]);
            }
        });

        Session::flash('success', 'Branch updated successfully.');

        return redirect()->route('branch.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:branches,id'],
        ]);

        $branch = Branch::query()
            ->whereIn('id', $this->branchContext()->accessibleBranchIds(auth()->user()))
            ->findOrFail($validated['id']);

        $hasDependencies = $branch->warehouses()->exists()
            || $branch->purchaseOrders()->exists()
            || $branch->salesOrders()->exists()
            || $branch->stockBalances()->exists()
            || $branch->inventoryAdjustments()->exists()
            || $branch->supplierReturns()->exists()
            || $branch->cashierShifts()->exists()
            || $branch->inventoryTransactions()->exists()
            || $branch->branchPrices()->exists()
            || $branch->userBranchRoles()->exists()
            || Payment::query()->where('branch_id', $branch->id)->exists()
            || InterBranchTransfer::query()
                ->where(function ($query) use ($branch) {
                    $query->where('from_branch_id', $branch->id)
                        ->orWhere('to_branch_id', $branch->id);
                })->exists();

        if ($hasDependencies) {
            return response()->json([
                'message' => 'Branch cannot be deleted because related transactions or assignments already exist.',
            ], 422);
        }

        $branch->delete();

        return response()->json(['success' => 'Branch deleted successfully.']);
    }

    public function switchCurrent(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $this->branchContext()->setCurrentBranch($request->user(), (int) $validated['branch_id']);

        return redirect()->back()->with('success', 'Current branch switched successfully.');
    }
}
