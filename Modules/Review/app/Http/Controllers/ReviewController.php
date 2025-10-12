<?php

namespace Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Modules\Review\Http\Requests\CreateReviewRequest;
use Modules\Review\Http\Requests\UpdateReviewRequest;
use Modules\Review\Http\Resources\ReviewResource;
use Modules\Review\Services\ReviewService;
use Illuminate\Http\JsonResponse;
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

    /**
     * Store a newly created review for an order.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->createReview( $request->all());

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
