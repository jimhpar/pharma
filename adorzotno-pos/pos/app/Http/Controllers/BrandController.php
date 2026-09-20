<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandCertification;
use App\Models\BrandTag;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    use ImageTrait;
    public function show()
    {
        $brand = Brand::orderBy('sort_order')->orderBy('id', 'desc')->get();

        return view('brand.index', compact('brand'));
    }

    public function list(Request $request)
    {
        $brand = Brand::query()
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('sort_order')
            ->orderBy('id', 'desc');
        return DataTables()->of($brand)
            ->addColumn('logo', function (Brand $brand){
                if (isset($brand->logo)) {
                    return '<img height="50px" width="50px" src="'.url($brand->logo).'" alt="">';
                }
                return '';
            })
            ->addColumn('status', function (Brand $brand)
            {
                if ($brand->status === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }
                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align'=>'center',
            ])
            ->rawColumns(['logo', 'status'])
            ->make(true);
    }

    public function create()
    {       
        return view('brand.create');
    }

    public function store(Request $request)
    {         
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands,slug',
            'sort_order' => 'required|integer|min:0|unique:brands,sort_order',
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp,gif',
            'background_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'rating' => 'nullable|numeric|min:0|max:5',
            'products_count' => 'nullable|integer|min:0',
            'reviews_count' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'founded_year' => 'nullable|integer|min:1800|max:2100',
            'headquarter_address' => 'nullable|string',
            'employees_count' => 'nullable|integer|min:0',
            'is_verified' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'certifications' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'featured' => 'required|boolean',
        ]);    

        $brand = Brand::create([
            'name' => $request->name,
            'slug' => $request->slug,        
            'logo' => $this->save_image('brandLogo', $validated['logo']),
            'background_image' => $request->hasFile('background_image') ? $this->save_image('brandLogo', $request->file('background_image')) : null,
            'rating' => $validated['rating'] ?? null,
            'products_count' => $validated['products_count'] ?? 0,
            'reviews_count' => $validated['reviews_count'] ?? 0,
            'description' => $validated['description'] ?? null,
            'founded_year' => $validated['founded_year'] ?? null,
            'headquarter_address' => $validated['headquarter_address'] ?? null,
            'employees_count' => $validated['employees_count'] ?? null,
            'is_verified' => $request->boolean('is_verified'),
            'sort_order' => $validated['sort_order'],
            'status' => $request->status,
            'is_featured' => $request->has('featured') ? 1 : 0,
        ]);

        $this->syncBrandTags($brand, $validated['tags'] ?? '');
        $this->syncBrandCertifications($brand, $validated['certifications'] ?? '');

        return redirect()->route('brand.show')->with('success', 'Brand created successfully.');
    }

    public function edit($id)
    {    
        $brand = Brand::with(['brandTags', 'brandCertifications'])->findOrFail($id);
        return view('brand.edit', compact('brand'));
    }   

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($id),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('brands', 'sort_order')->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
            'featured' => 'required|boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'background_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'rating' => 'nullable|numeric|min:0|max:5',
            'products_count' => 'nullable|integer|min:0',
            'reviews_count' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'founded_year' => 'nullable|integer|min:1800|max:2100',
            'headquarter_address' => 'nullable|string',
            'employees_count' => 'nullable|integer|min:0',
            'is_verified' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'certifications' => 'nullable|string',
        ]);

        $brandLogo = $brand->logo;
        $brandBackgroundImage = $brand->background_image;

        if ($request->hasFile('logo')) {
            if (!empty($brand->logo)) {
                $this->deleteImage($brand->logo);
            }

            $brandLogo = $this->save_image('brandLogo', $request->file('logo'));
        }

        if ($request->hasFile('background_image')) {
            if (!empty($brand->background_image)) {
                $this->deleteImage($brand->background_image);
            }

            $brandBackgroundImage = $this->save_image('brandLogo', $request->file('background_image'));
        }

        $brand->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'sort_order' => $validated['sort_order'],
            'status' => $validated['status'],
            'is_featured' => $request->boolean('featured'),
            'logo' => $brandLogo,
            'background_image' => $brandBackgroundImage,
            'rating' => $validated['rating'] ?? null,
            'products_count' => $validated['products_count'] ?? 0,
            'reviews_count' => $validated['reviews_count'] ?? 0,
            'description' => $validated['description'] ?? null,
            'founded_year' => $validated['founded_year'] ?? null,
            'headquarter_address' => $validated['headquarter_address'] ?? null,
            'employees_count' => $validated['employees_count'] ?? null,
            'is_verified' => $request->boolean('is_verified'),
        ]);

        $this->syncBrandTags($brand, $validated['tags'] ?? '');
        $this->syncBrandCertifications($brand, $validated['certifications'] ?? '');

        Session::flash('success', 'Brand Updated Successfully!');
        return redirect()->route('brand.show');
    }
    
    public function delete(Request $request): JsonResponse
    {
        $brand = Brand::query()->where('id', $request->id)->first();
        if (!empty($brand)) {
            $brand->delete();
        }
        return response()->json();
    }

    private function syncBrandTags(Brand $brand, ?string $tags): void
    {
        $brand->brandTags()->delete();

        foreach ($this->splitTextareaLines($tags) as $tag) {
            BrandTag::query()->create([
                'brand_id' => $brand->id,
                'tag' => $tag,
                'status' => 'active',
            ]);
        }
    }

    private function syncBrandCertifications(Brand $brand, ?string $certifications): void
    {
        $brand->brandCertifications()->delete();

        foreach ($this->splitTextareaLines($certifications) as $certification) {
            BrandCertification::query()->create([
                'brand_id' => $brand->id,
                'certification' => $certification,
                'status' => 'active',
            ]);
        }
    }

    private function splitTextareaLines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
