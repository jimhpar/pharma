<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReviewController extends Controller
{
    public function show()
    {
        return view('review.index');
    }

    public function list()
    {
        $reviews = Review::query()
            ->with(['product', 'customer.user'])
            ->latest('id');

        return DataTables()->of($reviews)
            ->addColumn('product', function (Review $review) {
                return $review->product?->name ?? 'N/A';
            })
            ->addColumn('customer', function (Review $review) {
                return $review->customer?->user?->name ?? 'Walk-in Customer';
            })
            ->addColumn('rating', function (Review $review) {
                return number_format((float) $review->rating, 1);
            })
            ->addColumn('verified_purchase', function (Review $review) {
                return $review->is_verified_purchase
                    ? '<span class="badge bg-light-success">Verified</span>'
                    : '<span class="badge bg-light-secondary">No</span>';
            })
            ->addColumn('status_badge', function (Review $review) {
                if (strtolower((string) $review->status) === 'approved') {
                    return '<span class="badge bg-light-success">Approved</span>';
                }

                return '<span class="badge bg-light-warning text-dark">Pending</span>';
            })
            ->addColumn('approved_date', function (Review $review) {
                return DateFormatter::dateTime($review->approved_at, 'Pending');
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['verified_purchase', 'status_badge'])
            ->make(true);
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:reviews,id',
        ]);

        $review = Review::query()->findOrFail($validated['id']);

        $review->update([
            'status' => 'approved',
            'approved_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully.',
        ]);
    }
}
