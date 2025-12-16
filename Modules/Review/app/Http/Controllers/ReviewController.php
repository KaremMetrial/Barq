<?php

namespace Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Modules\Review\Http\Requests\CreateReviewRequest;
use Modules\Review\Http\Requests\UpdateReviewRequest;
use Modules\Review\Http\Resources\ReviewResource;
use Modules\Review\Http\Resources\VendorReviewResource;
use Modules\Review\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Review\Models\Review;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(protected ReviewService $reviewService)
    {
    }

    /**
     * Display a listing of the reviews for an order.
     */
    public function index(int $orderId): JsonResponse
    {
        $reviews = $this->reviewService->getReviewsByOrderId($orderId);

        return $this->successResponse([
            "reviews" => ReviewResource::collection($reviews),
            'pagination' => new PaginationResource($reviews)
        ], __('message.success'));
    }
    public function storeIndex(Request $request, int $storeId): JsonResponse
    {
        // Extract filters from request
        $filters = $request->only([
            'rating',
            'replied',
            'min_rating',
            'max_rating',
            'from_date',
            'to_date',
            'sort_by',
            'sort_order',
            'per_page',
            'search'
        ]);

        $reviews = $this->reviewService->getReviewForStore($storeId, $filters);

        // Calculate average rating and other statistics
        $averageRating = $reviews->avg('rating');
        $totalReviews = $reviews->total();
        $repliedReviews = $reviews->whereNotNull('response')->count();
        $notRepliedReviews = $totalReviews - $repliedReviews;

        // Calculate rating distribution
        $ratingDistribution = [
            '5_star' => $reviews->where('rating', '>=', 4.5)->count(),
            '4_star' => $reviews->whereBetween('rating', [3.5, 4.5])->count(),
            '3_star' => $reviews->whereBetween('rating', [2.5, 3.5])->count(),
            '2_star' => $reviews->whereBetween('rating', [1.5, 2.5])->count(),
            '1_star' => $reviews->where('rating', '<', 1.5)->count(),
        ];

        return $this->successResponse([
            "reviews" => VendorReviewResource::collection($reviews),
            'pagination' => new PaginationResource($reviews),
            'statistics' => [
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews,
                'replied_reviews' => $repliedReviews,
                'not_replied_reviews' => $notRepliedReviews,
                'rating_distribution' => $ratingDistribution
            ],
            'filters' => $filters
        ], __('message.success'));
    }
    /**
     * Store a newly created review for an order.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->createReview($request->all());

        return $this->successResponse([
            'review' => new ReviewResource($review)
        ], __('message.success'));
    }

    /**
     * Show the specified review.
     */
    public function show(int $id): JsonResponse
    {
        // Get review by ID
        $review = $this->reviewService->getReviewById($id);

        return $this->successResponse([
            'review' => new ReviewResource($review)
        ], __('message.success'));
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, int $id): JsonResponse
    {
        // Update the review by ID
        $review = $this->reviewService->updateReview($id, $request->all());

        return $this->successResponse([
            'review' => new ReviewResource($review)
        ], __('message.success'));
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        // Delete the review by ID
        $isDeleted = $this->reviewService->deleteReview($id);

        return $this->successResponse(null, __('message.success'));
    }
}
