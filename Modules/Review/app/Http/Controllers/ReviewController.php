<?php

namespace Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected ReviewService $reviewService) {}

    /**
     * Display a listing of the reviews for an order.
     */
    public function index($orderId = null): JsonResponse
    {
        if(auth('admin')->check())
        {
            $this->authorize('viewAny', Review::class);
            $reviews = $this->reviewService->getAll();
        }else {
            $reviews = $this->reviewService->getReviewsByOrderId($orderId);
        }
        return $this->successResponse([
            "reviews" => ReviewResource::collection($reviews->load('order')),
            'pagination' => new PaginationResource($reviews)
        ], __('message.success'));
    }
    public function storeIndex(Request $request, \Modules\Store\Models\Store $store): JsonResponse
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

        $reviews = $this->reviewService->getReviewForStore($store->id, $filters);
        $statistics = $this->reviewService->getReviewStatsForStore($store->id, $filters);

        return $this->successResponse([
            "reviews" => VendorReviewResource::collection($reviews),
            'pagination' => new PaginationResource($reviews),
            'statistics' => $statistics,
            'filters' => $filters ? $filters : null,
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
        $this->authorize('view', $review);

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
        $review = $this->reviewService->getReviewById($id);
        $this->authorize('update', $review);
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
        $review = $this->reviewService->getReviewById($id);
        $this->authorize('delete', $review);
        $isDeleted = $this->reviewService->deleteReview($id);

        return $this->successResponse(null, __('message.success'));
    }
}
