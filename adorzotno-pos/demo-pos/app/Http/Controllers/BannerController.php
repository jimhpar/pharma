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
                if (isset($banner->image)) {
                    return '<img height="50px" width="50px" src="'.url($banner->image).'" alt="">';
                }
                return '';
            })
            ->addColumn('status', function (Banner $banner)
            {
                if ($banner->status === 'Active') {
                    return '<label class="btn btn-success">Active</label>';
                }
                return '<label class="btn btn-danger">Inactive</label>';
            })
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
            'banner_type' => 'required|string|max:100',
            'status' => 'required|in:Active,Inactive',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif',
        ]);    

        Banner::create([
            'title' => $validated['title'] ?? null,
            'banner_url' => $validated['banner_url'] ?? null,
            'image' => $this->save_image('bannerImage', $validated['image']),  
            'banner_type' => $validated['banner_type'],
            'status' => $validated['status'],
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
            'banner_type' => 'required|string|max:100',
            'status' => 'required|in:Active,Inactive',
        ]);

        $banner = Banner::findOrFail($bannerId);
        $bannerImage = $banner->image;

        if ($request->hasFile('image')) {
            $this->deleteImage($banner->image);
            $bannerImage = $this->save_image('bannerImage', $validated['image']);
        }

        $banner->update([
            'title' => $validated['title'] ?? null,
            'banner_url' => $validated['banner_url'] ?? null,
            'image' => $bannerImage,
            'status' => $validated['status'],
            'banner_type' => $validated['banner_type'],
        ]);

        Session::flash('success', 'Banner Updated Successfully!');
        return redirect()->route('banner.show');
    }
    public function delete(Request $request): JsonResponse
    {
        $banner = Banner::query()->where('id', $request->id)->first();
        if (!empty($banner)) {
            $this->deleteImage($banner->image);
            $banner->delete();
        }
        return response()->json();
    }
}
