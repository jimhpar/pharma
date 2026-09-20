<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function show()
    {
        return view('expense_category.index');
    }

    public function list(Request $request)
    {
        $categories = ExpenseCategory::query()
            ->with('parent:id,name')
            ->when($request->filter_status !== null && $request->filter_status !== '', fn ($q) => $q->where('is_active', $request->filter_status === 'active'))
            ->orderBy('parent_id')
            ->orderBy('name');

        return DataTables()->of($categories)
            ->addColumn('parent_name', fn (ExpenseCategory $category) => $category->parent?->name ?? 'Main Category')
            ->addColumn('status', function (ExpenseCategory $category) {
                return $category->is_active
                    ? '<label class="btn btn-success">Active</label>'
                    : '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['status'])
            ->make(true);
    }

    public function create()
    {
        $parents = ExpenseCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('expense_category.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);

        ExpenseCategory::query()->create($validated);

        return redirect()->route('expenseCategory.show')->with('success', 'Expense category created successfully.');
    }

    public function edit(int $id)
    {
        $category = ExpenseCategory::query()->findOrFail($id);
        $parents = ExpenseCategory::query()
            ->whereNull('parent_id')
            ->where('id', '!=', $id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('expense_category.edit', compact('category', 'parents'));
    }

    public function update(Request $request, int $id)
    {
        $category = ExpenseCategory::query()->findOrFail($id);
        $validated = $this->validateCategory($request, $id);

        $category->update($validated);

        Session::flash('success', 'Expense category updated successfully.');

        return redirect()->route('expenseCategory.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:expense_categories,id'],
        ]);

        $category = ExpenseCategory::query()->findOrFail($validated['id']);

        if ($category->children()->exists() || $category->expenses()->exists()) {
            return response()->json([
                'message' => 'This category has sub-categories or expenses and cannot be deleted.',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => 'Expense category deleted successfully.']);
    }

    private function validateCategory(Request $request, ?int $categoryId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('expense_categories', 'code')->ignore($categoryId)],
            'parent_id' => ['nullable', 'exists:expense_categories,id', Rule::notIn([$categoryId])],
            'status' => ['required', 'in:active,inactive'],
        ]);

        return [
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['status'] === 'active',
        ];
    }
}
