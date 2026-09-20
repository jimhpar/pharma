<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Review;
use App\Models\SalesOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends BaseApiController
{
    /**
     * Get product reviews
     *
     * @param  string  $slug
     */
    public function productReviews($slug, Request $request): JsonResponse
    {
        $product = Product::query()->where('slug', $slug)->first();

        if (! $product) {
            return $this->notFound('Product not found');
        }

        $reviews = Review::where('product_id', $product->id)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) = ?', ['approved']);
            })
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return $this->success([
            'product' => $product,
            'reviews' => $reviews->toArray(),
        ], 'Reviews retrieved successfully', 200);
    }

    /**
     * Create review
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|integer|exists:products,id|required_without:product_slug',
            'product_slug' => 'nullable|string|exists:products,slug|required_without:product_id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $customer = $this->authenticatedCustomer(true);
            $product = Product::query()
                ->when($request->product_slug, fn ($query) => $query->where('slug', $request->product_slug))
                ->when(! $request->product_slug, fn ($query) => $query->where('id', $request->product_id))
                ->firstOrFail();

            // Check if user has purchased this product
            $hasPurchased = SalesOrderItem::whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer?->id);
            })->whereHas('sku', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })->exists();

            if (! $hasPurchased) {
                return $this->error(null, 'You can only review products you have purchased', 403);
            }

            // Check if user already reviewed this product
            $alreadyReviewed = Review::where('customer_id', $customer?->id)
                ->where('product_id', $product->id)
                ->exists();

            if ($alreadyReviewed) {
                return $this->error(null, 'You have already reviewed this product', 400);
            }

            $review = Review::create([
                'product_id' => $product->id,
                'customer_id' => $customer?->id,
                'rating' => round((float) $request->rating, 2),
                'comment' => $request->comment ?? null,
                'status' => 'pending',
                'is_verified_purchase' => true,
            ]);

            return $this->success([
                'review' => $review->load('customer'),
            ], 'Review created successfully', 201);
        } catch (\Exception $e) {
            return $this->serverError('Failed to create review: '.$e->getMessage());
        }
    }

    /**
     * Update review
     *
     * @param  int  $id
     */
    public function update($id, Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $review = Review::find($id);

        if (! $review) {
            return $this->notFound('Review not found');
        }

        $customer = $this->authenticatedCustomer();

        if ($review->customer_id !== $customer?->id) {
            return $this->error(null, 'You can only update your own reviews', 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            if ($request->has('rating')) {
                $review->rating = round((float) $request->rating, 2);
            }
            if ($request->comment !== null) {
                $review->comment = $request->comment;
            }
            $review->status = 'pending';
            $review->approved_at = null;

            $review->save();

            return $this->success([
                'review' => $review->load('customer'),
            ], 'Review updated successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to update review: '.$e->getMessage());
        }
    }

    /**
     * Delete review
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        if (request()->user() === null) {
            return $this->unauthorized('Not authenticated');
        }

        $review = Review::find($id);

        if (! $review) {
            return $this->notFound('Review not found');
        }

        $customer = $this->authenticatedCustomer();

        if ($review->customer_id !== $customer?->id) {
            return $this->error(null, 'You can only delete your own reviews', 403);
        }

        try {
            $review->delete();

            return $this->success(null, 'Review deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete review: '.$e->getMessage());
        }
    }
}
