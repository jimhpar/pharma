<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ImageTrait;
    public function show()
    {
        $category = Category::orderBy('sort_order')->orderBy('id', 'desc')->get();

        return view('category.index', compact('category'));
    }

    public function list()
    {     
        $category = Category::query()
            ->orderBy('sort_order')
            ->orderBy('id', 'desc');
        return DataTables()->of($category)
            ->addColumn('image', function (Category $category){
                if (isset($category->image)) {
                    return '<img height="50px" width="50px" src="'.url($category->image).'" alt="">';
                }
                return '';
            })
            ->addColumn('icon', function (Category $category){
                if (isset($category->icon)) {
                    return '<img height="50px" width="50px" src="'.url($category->icon).'" alt="">';
                }
                return '';
            })
            ->addColumn('status', function (Category $category)
            {
                if ($category->status === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }
                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align'=>'center',
            ])
            ->rawColumns(['image', 'icon', 'status'])
            ->make(true);
    }

    public function create()
    {
        $parent = $this->getCategoryOptions();
        $lastSerial = (int) Category::query()->max('sort_order');

        return view('category.create', compact('parent', 'lastSerial'));
    }

    public function store(Request $request)
    {         
        $validated = $this->validate($request, [
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'required|integer|min:0|unique:categories,sort_order',
            'status' => 'required|in:active,inactive',
            'featured' => 'required|boolean',
            'popular' => 'required|boolean',
            'top_deal' => 'required|boolean',
            'icon' => 'required|image|mimes:jpg,jpeg,png,webp,gif',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
        ]);    

        Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'icon' => $this->save_image('categoryImage', $validated['icon']),  
            'image' => $request->hasFile('image') ? $this->save_image('categoryImage', $request->file('image')) : null,
            'parent_id' => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'],
            'status' => $validated['status'],
            'is_featured' => $request->boolean('featured'),
            'is_popular' => $request->boolean('popular'),
            'is_top_deal' => $request->boolean('top_deal'),
        ]);

        return redirect()->route('category.show')->with('success', 'Category created successfully.');
    }

    public function edit($id)
    {
        $parent = $this->getCategoryOptions($id);
        $category = Category::findOrFail($id);
        $lastSerial = (int) Category::query()
            ->whereKeyNot($category->id)
            ->max('sort_order');

        return view('category.edit', compact('category', 'parent', 'lastSerial'));
    }   

    public function update(Request $request, $id)
    {      
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($id),
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$id]),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('categories', 'sort_order')->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
            'featured' => 'required|boolean',
            'popular' => 'required|boolean',
            'top_deal' => 'required|boolean',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
        ]);

        $categoryIcon = $category->icon;
        $categoryImage = $category->image;

        if ($request->hasFile('icon')) {
            if (!empty($category->icon)) {
                $this->deleteImage($category->icon);
            }

            $categoryIcon = $this->save_image('categoryImage', $request->file('icon'));
        }

        if ($request->hasFile('image')) {
            if (!empty($category->image)) {
                $this->deleteImage($category->image);
            }

            $categoryImage = $this->save_image('categoryImage', $request->file('image'));
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'parent_id' => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'],
            'status' => $validated['status'],
            'is_featured' => $request->boolean('featured'),
            'is_popular' => $request->boolean('popular'),
            'is_top_deal' => $request->boolean('top_deal'),
            'icon' => $categoryIcon,
            'image' => $categoryImage,
        ]);

        Session::flash('success', 'Category Updated Successfully!');
        return redirect()->route('category.show');
    }
    
    public function delete(Request $request): JsonResponse
    {
        $category = Category::query()->where('id', $request->id)->first();
        if (!empty($category)) {
            $category->delete();
        }
        return response()->json();
    }

    private function getCategoryOptions(?int $excludeId = null)
    {
        $categories = Category::query()
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->orderBy('name')
            ->get();

        $grouped = $categories->groupBy(fn (Category $category) => $category->parent_id ?: 0);
        $ordered = collect();

        $walk = function ($parentId, string $prefix = '') use (&$walk, $grouped, $ordered) {
            foreach ($grouped->get($parentId, collect())->sortBy('name') as $category) {
                $category->display_name = $prefix . $category->name;
                $ordered->push($category);
                $walk($category->id, $prefix . '— ');
            }
        };

        $walk(0);

        return $ordered;
    }
}
