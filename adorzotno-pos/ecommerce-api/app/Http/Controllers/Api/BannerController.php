<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Banner::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            });

        if ($request->filled('banner_type')) {
            $query->where('banner_type', $request->banner_type);
        }

        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        $banners = $query
            ->orderByDesc('id')
            ->get();

        return $this->success([
            'banners' => $banners,
        ], 'Banners retrieved successfully', 200);
    }

    public function show($id): JsonResponse
    {
        $banner = Banner::query()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['active']);
            })
            ->find($id);

        if (! $banner) {
            return $this->notFound('Banner not found');
        }

        return $this->success([
            'banner' => $banner,
        ], 'Banner retrieved successfully', 200);
    }
}
