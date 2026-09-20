<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryPromotion;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CategoryPromotionController extends Controller
{
    use ImageTrait;

    public function show()
    {
        $categoryPromotions = CategoryPromotion::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('category_promotion.index', compact('categoryPromotions'));
    }

    public function list()
    {
        $categoryPromotions = CategoryPromotion::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        return DataTables()->of($categoryPromotions)
            ->addColumn('category', function (CategoryPromotion $categoryPromotion) {
                return $categoryPromotion->category?->name ?? 'N/A';
            })
            ->addColumn('icon', function (CategoryPromotion $categoryPromotion) {
                if (isset($categoryPromotion->icon)) {
                    return '<img height="50px" width="50px" src="' . url($categoryPromotion->icon) . '" alt="">';
                }

                return '';
            })
            ->addColumn('status', function (CategoryPromotion $categoryPromotion) {
                if ($categoryPromotion->status === 'Active') {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['icon', 'status'])
            ->make(true);
    }

    public function create()
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('category_promotion.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'icon' => 'required|image|mimes:jpg,jpeg,png,webp,gif',
            'status' => 'required|in:Active,Inactive',
            'sort_order' => 'required|integer|min:0|unique:category_promotion,sort_order',
        ]);

        CategoryPromotion::query()->create([
            'title' => $validated['title'],
            'category_id' => $validated['category_id'],
            'icon' => $this->save_image('categoryPromotion', $validated['icon']),
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'],
        ]);

        return redirect()->route('categoryPromotion.show')->with('success', 'Category promotion created successfully.');
    }

    public function edit($id)
    {
        $categoryPromotion = CategoryPromotion::query()->findOrFail($id);
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('category_promotion.edit', compact('categoryPromotion', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $categoryPromotion = CategoryPromotion::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'status' => 'required|in:Active,Inactive',
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('category_promotion', 'sort_order')->ignore($id),
            ],
        ]);

        $iconPath = $categoryPromotion->icon;

        if ($request->hasFile('icon')) {
            if (!empty($categoryPromotion->icon)) {
                $this->deleteImage($categoryPromotion->icon);
            }

            $iconPath = $this->save_image('categoryPromotion', $request->file('icon'));
        }

        $categoryPromotion->update([
            'title' => $validated['title'],
            'category_id' => $validated['category_id'],
            'icon' => $iconPath,
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'],
        ]);

        Session::flash('success', 'Category promotion updated successfully.');

        return redirect()->route('categoryPromotion.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $categoryPromotion = CategoryPromotion::query()->where('id', $request->id)->first();

        if (!empty($categoryPromotion)) {
            if (!empty($categoryPromotion->icon)) {
                $this->deleteImage($categoryPromotion->icon);
            }

            $categoryPromotion->delete();
        }

        return response()->json();
    }
}
