<?php

namespace Modules\Coupon\Services;

use App\Models\CouponReview;
use Modules\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;

class CouponReviewService
{
    public function getCouponReviewStats(int $couponId): array
    {
        $reviews = CouponReview::where('coupon_id', $couponId)->approved();
        
        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating');
        
        $ratingBreakdown = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->clone()->where('rating', $i)->count();
            $ratingBreakdown[$i] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0
            ];
        }

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating ? round($averageRating, 1) : null,
            'rating_breakdown' => $ratingBreakdown,
            'verified_purchases' => $reviews->clone()->where('is_verified_purchase', true)->count()
        ];
    }

    public function getTopRatedCoupons(int $limit = 10): Collection
    {
        return Coupon::whereHas('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->withCount('approvedReviews')
            ->having('approved_reviews_count', '>=', 3) // Minimum 3 reviews
            ->orderByDesc('approved_reviews_avg_rating')
            ->limit($limit)
            ->get();
    }

    public function canUserReview(int $couponId, int $userId): array
    {
        $existingReview = CouponReview::where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            return [
                'can_review' => false,
                'reason' => 'already_reviewed'
            ];
        }

        return [
            'can_review' => true,
            'reason' => null
        ];
    }

    public function moderateReview(int $reviewId, string $status, ?string $moderatorNote = null): CouponReview
    {
        $review = CouponReview::findOrFail($reviewId);
        
        $review->update([
            'status' => $status,
            'moderator_note' => $moderatorNote
        ]);

        return $review;
    }

    public function getReviewsForModeration(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = CouponReview::with(['coupon:id,code', 'user:id,name']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->where('is_verified_purchase', true);
        }

        return $query->latest()->paginate(20);
    }
}