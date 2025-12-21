<?php

namespace Modules\Coupon\Http\Controllers;

use App\Models\CouponReview;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Http\Requests\StoreCouponReviewRequest;
use Modules\Coupon\Http\Resources\CouponReviewResource;
use Modules\Coupon\Services\CouponReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

class CouponReviewController extends Controller
{
    public function __construct(
        private CouponReviewService $reviewService
    ) {}

    public function index(Request $request, $couponId): JsonResponse
    {
        $coupon = Coupon::findOrFail($couponId);
        
        $reviews = CouponReview::where('coupon_id', $couponId)
            ->approved()
            ->with('user:id,name')
            ->when($request->rating, fn($q) => $q->byRating($request->rating))
            ->latest('reviewed_at')
            ->paginate(10);

        $stats = $this->reviewService->getCouponReviewStats($couponId);

        return response()->json([
            'reviews' => CouponReviewResource::collection($reviews),
            'stats' => $stats
        ]);
    }

    public function store(StoreCouponReviewRequest $request, $couponId): JsonResponse
    {
        $coupon = Coupon::findOrFail($couponId);
        $userId = auth()->id();

        $canReview = $this->reviewService->canUserReview($couponId, $userId);
        
        if (!$canReview['can_review']) {
            return response()->json(['message' => 'You have already reviewed this coupon'], 422);
        }

        // Check if user has used this coupon (verified purchase)
        $hasUsedCoupon = \App\Models\CouponUsage::where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->exists();

        $review = CouponReview::create([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'reviewed_at' => now(),
            'is_verified_purchase' => $hasUsedCoupon
        ]);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => new CouponReviewResource($review->load('user:id,name'))
        ], 201);
    }

    public function update(StoreCouponReviewRequest $request, $couponId, $reviewId): JsonResponse
    {
        $review = CouponReview::where('id', $reviewId)
            ->where('coupon_id', $couponId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'reviewed_at' => now(),
            'status' => CouponReview::STATUS_PENDING
        ]);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => new CouponReviewResource($review->load('user:id,name'))
        ]);
    }

    public function destroy($couponId, $reviewId): JsonResponse
    {
        $review = CouponReview::where('id', $reviewId)
            ->where('coupon_id', $couponId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'rating', 'verified_only']);
        $reviews = $this->reviewService->getReviewsForModeration($filters);

        return response()->json([
            'reviews' => CouponReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total()
            ]
        ]);
    }

    public function updateStatus(Request $request, $reviewId): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'moderator_note' => 'nullable|string|max:500'
        ]);

        $review = $this->reviewService->moderateReview(
            $reviewId, 
            $request->status, 
            $request->moderator_note
        );

        return response()->json([
            'message' => 'Review status updated successfully',
            'review' => new CouponReviewResource($review)
        ]);
    }

    public function topRated(): JsonResponse
    {
        $coupons = $this->reviewService->getTopRatedCoupons();
        
        return response()->json([
            'top_rated_coupons' => $coupons->map(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'average_rating' => round($coupon->approved_reviews_avg_rating, 1),
                    'total_reviews' => $coupon->approved_reviews_count
                ];
            })
        ]);
    }
}