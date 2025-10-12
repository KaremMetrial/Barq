<?php

namespace Modules\Review\Services;

use App\Traits\FileUploadTrait;
use Modules\Review\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Review\Repositories\ReviewRepository;

class ReviewService
{
    use FileUploadTrait;

    public function __construct(
        protected ReviewRepository $reviewRepository
    ) {}

    /**
     * Get all reviews by a specific order ID.
     */
    public function getReviewsByOrderId(int $orderId): LengthAwarePaginator
    {
        return $this->reviewRepository->getReviewsByOrderId($orderId);
    }
    /**
     * Create a new review.
     */
    public function createReview(array $data): ?Review
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['image'])) {
                $data['image'] = $this->upload(request(), $data['image'], 'reviews');
            }
            $data = array_filter($data, fn($value) => !blank($value));

            return $this->reviewRepository->create($data);
        });
    }

    /**
     * Get a review by its ID.
     */
    public function getReviewById(int $id): ?Review
    {
        return $this->reviewRepository->find($id, ['order']);
    }

    /**
     * Update a review.
     */
    public function updateReview(int $id, array $data): ?Review
    {
        return DB::transaction(function () use ($data, $id) {
            if (isset($data['image'])) {
                $data['image'] = $this->upload(request(), $data['image'], 'reviews');
            }
            $data = array_filter($data, fn($value) => !blank($value));

            return $this->reviewRepository->update($id, $data);
        });
    }

    /**
     * Delete a review.
     */
    public function deleteReview(int $id): bool
    {
        return $this->reviewRepository->delete($id);
    }
}
