<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class BannerController extends Controller
{
    use ImageTrait;
    public function show()
    {
        $banners = Banner::orderBy('id', 'desc')->get();

        return view('banner.index', compact('banners'));
    }

    public function list(Request $request)
    {     
        $banner = Banner::query()
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', ucfirst($v)));
        return DataTables()->of($banner)
            ->addColumn('image', function (Banner $banner){
                if (isset($banner->image_path)) {
                    return '<img height="50px" width="50px" src="'.url($banner->image_path).'" alt="">';
                }
                return '';
            })
            ->addColumn('status', function (Banner $banner)
            {
                if (strtolower($banner->status) === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }
                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->editColumn('position', fn (Banner $banner) => $banner->position ?? 'N/A')
            ->setRowAttr([
                'align'=>'center',
            ])
            ->rawColumns(['image', 'status'])
            ->make(true);
    }

    public function create()
    {
        return view('banner.create');
    }

    public function store(Request $request)
    {         
        $validated = $this->validate($request, [
            'title' => 'nullable|string|max:255',
            'banner_url' => 'nullable|string|max:255',
            'banner_type' => 'required|in:slider,homepage_middle_1,homepage_middle_2',
            'position' => 'nullable|required_if:banner_type,homepage_middle_1,homepage_middle_2|in:small_top,small_bottom,large',
            'status' => 'required|in:active,inactive',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif',
        ]);    

        Banner::create([
            'title' => $validated['title'] ?? null,
            'banner_url' => $validated['banner_url'] ?? null,
            'image_path' => $this->save_image('bannerImage', $validated['image']),  
            'banner_type' => $validated['banner_type'],
            'position' => $validated['banner_type'] === 'slider' ? null : ($validated['position'] ?? null),
            'status' => strtolower($validated['status']),
        ]);

        return redirect()->route('banner.show')->with('success', 'Banner created successfully.');
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);

        return view('banner.edit', compact('banner'));
    }

    public function update(Request $request, $bannerId)
    {
        $validated = $this->validate($request, [
            'title' => 'nullable|string|max:255',
            'banner_url' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif',
            'banner_type' => 'required|in:slider,homepage_middle_1,homepage_middle_2',
            'position' => 'nullable|required_if:banner_type,homepage_middle_1,homepage_middle_2|in:small_top,small_bottom,large',
            'status' => 'required|in:active,inactive',
        ]);

        $banner = Banner::findOrFail($bannerId);
        $bannerImage = $banner->image_path;

        if ($request->hasFile('image')) {
            $this->deleteImage($banner->image_path);
            $bannerImage = $this->save_image('bannerImage', $validated['image']);
        }

        $banner->update([
            'title' => $validated['title'] ?? null,
            'banner_url' => $validated['banner_url'] ?? null,
            'image_path' => $bannerImage,
            'status' => strtolower($validated['status']),
            'banner_type' => $validated['banner_type'],
            'position' => $validated['banner_type'] === 'slider' ? null : ($validated['position'] ?? null),
        ]);

        Session::flash('success', 'Banner Updated Successfully!');
        return redirect()->route('banner.show');
    }
    public function delete(Request $request): JsonResponse
    {
        $banner = Banner::query()->where('id', $request->id)->first();
        if (!empty($banner)) {
            $this->deleteImage($banner->image_path);
            $banner->delete();
        }
        return response()->json();
    }
}
