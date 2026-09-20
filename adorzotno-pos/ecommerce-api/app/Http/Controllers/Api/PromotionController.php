<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\CategoryPromotion;
use Illuminate\Http\JsonResponse;

class PromotionController extends BaseApiController
{
    /**
     * Get active promotions
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $promotions = CategoryPromotion::query()
                ->whereRaw('LOWER(status) = ?', ['active'])
                ->with('category')
                ->withCount('products')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success([
                'promotions' => $promotions,
            ], 'Promotions retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve promotions: ' . $e->getMessage());
        }
    }
}
