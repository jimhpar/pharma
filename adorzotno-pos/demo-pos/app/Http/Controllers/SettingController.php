<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    use ImageTrait;

    public function show()
    {
        $settings = Setting::orderBy('id', 'desc')->get();

        return view('setting.index', compact('settings'));
    }

    public function list()
    {
        $settings = Setting::query()->orderBy('id', 'desc');

        return DataTables()->of($settings)
            ->addColumn('header_logo', function (Setting $setting) {
                if (isset($setting->header_logo)) {
                    return '<img height="50px" width="50px" src="' . url($setting->header_logo) . '" alt="">';
                }

                return '';
            })
            ->addColumn('footer_logo', function (Setting $setting) {
                if (isset($setting->footer_logo)) {
                    return '<img height="50px" width="50px" src="' . url($setting->footer_logo) . '" alt="">';
                }

                return '';
            })
            ->addColumn('office_address', function (Setting $setting) {
                return e(Str::limit((string) $setting->office_address, 60));
            })
            ->addColumn('homepage_about_text', function (Setting $setting) {
                return e(Str::limit(trim(strip_tags((string) $setting->homepage_about_text)), 80));
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['header_logo', 'footer_logo'])
            ->make(true);
    }

    public function create()
    {
        return view('setting.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'header_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'footer_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'office_address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'footer_gateway_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'homepage_about_text' => 'nullable|string',
            'homepage_notice' => 'nullable|string',
        ]);

        Setting::create([
            'company_name' => $validated['company_name'] ?? null,
            'header_logo' => $request->hasFile('header_logo') ? $this->save_image('settingImage', $request->file('header_logo')) : null,
            'footer_logo' => $request->hasFile('footer_logo') ? $this->save_image('settingImage', $request->file('footer_logo')) : null,
            'office_address' => $validated['office_address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'footer_gateway_banner' => $request->hasFile('footer_gateway_banner') ? $this->save_image('settingImage', $request->file('footer_gateway_banner')) : null,
            'homepage_about_text' => $validated['homepage_about_text'] ?? null,
            'homepage_notice' => $validated['homepage_notice'] ?? null,
        ]);

        return redirect()->route('setting.show')->with('success', 'Setting created successfully.');
    }

    public function edit($id)
    {
        $setting = Setting::findOrFail($id);

        return view('setting.edit', compact('setting'));
    }

    public function update(Request $request, $id)
    {
        $setting = Setting::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'header_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'footer_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'office_address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'footer_gateway_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'homepage_about_text' => 'nullable|string',
            'homepage_notice' => 'nullable|string',
        ]);

        $headerLogo = $setting->header_logo;
        $footerLogo = $setting->footer_logo;
        $footerGatewayBanner = $setting->footer_gateway_banner;

        if ($request->hasFile('header_logo')) {
            if (!empty($setting->header_logo)) {
                $this->deleteImage($setting->header_logo);
            }

            $headerLogo = $this->save_image('settingImage', $request->file('header_logo'));
        }

        if ($request->hasFile('footer_logo')) {
            if (!empty($setting->footer_logo)) {
                $this->deleteImage($setting->footer_logo);
            }

            $footerLogo = $this->save_image('settingImage', $request->file('footer_logo'));
        }

        if ($request->hasFile('footer_gateway_banner')) {
            if (!empty($setting->footer_gateway_banner)) {
                $this->deleteImage($setting->footer_gateway_banner);
            }

            $footerGatewayBanner = $this->save_image('settingImage', $request->file('footer_gateway_banner'));
        }

        $setting->update([
            'company_name' => $validated['company_name'] ?? null,
            'header_logo' => $headerLogo,
            'footer_logo' => $footerLogo,
            'office_address' => $validated['office_address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'footer_gateway_banner' => $footerGatewayBanner,
            'homepage_about_text' => $validated['homepage_about_text'] ?? null,
            'homepage_notice' => $validated['homepage_notice'] ?? null,

        ]);

        Session::flash('success', 'Setting Updated Successfully!');
        return redirect()->route('setting.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $setting = Setting::query()->where('id', $request->id)->first();

        if (!empty($setting)) {
            $this->deleteImage($setting->header_logo);
            $this->deleteImage($setting->footer_logo);
            $this->deleteImage($setting->footer_gateway_banner);
            $setting->delete();
        }

        return response()->json();
    }
}
