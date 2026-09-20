<?php

namespace App\Http\Controllers\Api;

use App\Models\Faq;
use Illuminate\Http\JsonResponse;

class FaqController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $faqs = Faq::query()
            ->orderBy('id')
            ->get(['id', 'question', 'answer']);

        return $this->success([
            'faqs' => $faqs,
        ], 'FAQs retrieved successfully', 200);
    }

    public function show($id): JsonResponse
    {
        $faq = Faq::query()
            ->select(['id', 'question', 'answer'])
            ->find($id);

        if (! $faq) {
            return $this->notFound('FAQ not found');
        }

        return $this->success([
            'faq' => $faq,
        ], 'FAQ retrieved successfully', 200);
    }
}
