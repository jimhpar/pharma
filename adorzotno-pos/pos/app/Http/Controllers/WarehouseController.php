<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function show()
    {
        return view('warehouse.index');
    }

    public function list(Request $request)
    {
        $branchIds = $this->branchContext()->accessibleBranchIds(auth()->user());
        $warehouses = Warehouse::query()
            ->with('branch')
            ->whereIn('branch_id', $branchIds)
            ->when($request->filter_status !== null && $request->filter_status !== '', fn ($q) => $q->where('is_active', $request->filter_status === 'active'))
            ->orderBy('name')
            ->orderBy('id', 'desc');

        return DataTables()->of($warehouses)
            ->addColumn('branch', function (Warehouse $warehouse) {
                return $warehouse->branch?->name ?? 'N/A';
            })
            ->addColumn('default_status', function (Warehouse $warehouse) {
                if ($warehouse->is_default) {
                    return '<label class="btn btn-info">Default</label>';
                }

                return '<label class="btn btn-secondary">No</label>';
            })
            ->addColumn('negative_stock', function (Warehouse $warehouse) {
                if ($warehouse->allow_negative_stock) {
                    return '<label class="btn btn-warning">Allowed</label>';
                }

                return '<label class="btn btn-secondary">Blocked</label>';
            })
            ->addColumn('status', function (Warehouse $warehouse) {
                if ($warehouse->is_active) {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['default_status', 'negative_stock', 'status'])
            ->make(true);
    }

    public function create()
    {
        $branches = $this->branchContext()->accessibleBranches(auth()->user());

        return view('warehouse.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateWarehouse($request);

        DB::transaction(function () use ($validated) {
            if ($validated['is_default']) {
                Warehouse::query()
                    ->where('branch_id', $validated['branch_id'])
                    ->update(['is_default' => false]);
            }

            Warehouse::query()->create($validated);
        });

        return redirect()->route('warehouse.show')->with('success', 'Warehouse created successfully.');
    }

    public function edit($id)
    {
        $warehouse = Warehouse::query()
            ->whereIn('branch_id', $this->branchContext()->accessibleBranchIds(auth()->user()))
            ->findOrFail($id);
        $branches = $this->branchContext()->accessibleBranches(auth()->user());

        return view('warehouse.edit', compact('warehouse', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::query()->findOrFail($id);
        $validated = $this->validateWarehouse($request, $warehouse->id);

        DB::transaction(function () use ($warehouse, $validated) {
            if ($validated['is_default']) {
                Warehouse::query()
                    ->where('branch_id', $validated['branch_id'])
                    ->where('id', '!=', $warehouse->id)
                    ->update(['is_default' => false]);
            }

            $warehouse->update($validated);
        });

        return redirect()->route('warehouse.show')->with('success', 'Warehouse updated successfully.');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:warehouses,id'],
        ]);

        $warehouse = Warehouse::query()->findOrFail($validated['id']);

        $hasDependencies = InventoryBatch::query()->where('warehouse_id', $warehouse->id)->exists()
            || $warehouse->purchaseOrders()->exists()
            || $warehouse->salesOrders()->exists()
            || $warehouse->stockBalances()->exists()
            || $warehouse->inventoryAdjustments()->exists()
            || $warehouse->supplierReturns()->exists()
            || $warehouse->cashierShifts()->exists()
            || $warehouse->inventoryTransactions()->exists()
            || $warehouse->outgoingInterBranchTransfers()->exists()
            || $warehouse->incomingInterBranchTransfers()->exists();

        if ($hasDependencies) {
            return response()->json([
                'message' => 'Warehouse cannot be deleted because related transactions already exist.',
            ], 422);
        }

        if (!empty($warehouse)) {
            DB::transaction(function () use ($warehouse) {
                $branchId = $warehouse->branch_id;
                $wasDefault = $warehouse->is_default;

                $warehouse->delete();

                if ($wasDefault) {
                    $replacementWarehouse = Warehouse::query()
                        ->where('branch_id', $branchId)
                        ->orderBy('id')
                        ->first();

                    if (!empty($replacementWarehouse)) {
                        $replacementWarehouse->update(['is_default' => true]);
                    }
                }
            });
        }

        return response()->json(['success' => 'Warehouse deleted successfully.']);
    }

    private function validateWarehouse(Request $request, ?int $warehouseId = null): array
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->ignore($warehouseId),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')->ignore($warehouseId),
            ],
            'address' => ['nullable', 'string'],
            'is_default' => ['required', 'in:yes,no'],
            'allow_negative_stock' => ['required', 'in:yes,no'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (!$this->branchContext()->hasBranchAccess(auth()->user(), (int) $validated['branch_id'])) {
            abort(403, 'You do not have access to the selected branch.');
        }

        return [
            'branch_id' => (int) $validated['branch_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address' => $validated['address'] ?? null,
            'is_default' => $validated['is_default'] === 'yes',
            'allow_negative_stock' => $validated['allow_negative_stock'] === 'yes',
            'is_active' => $validated['status'] === 'active',
        ];
    }
}
