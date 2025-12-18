<?php

namespace Modules\Review\Repositories;

use Modules\Review\Models\Review;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Review\Repositories\Contracts\ReviewRepositoryInterface;

class ReviewRepository extends BaseRepository implements ReviewRepositoryInterface
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
    }
    public function create(array $data): ?Review
    {
        $review = $this->model->create($data);

        if (!empty($data['review_ratings'])) {
            $review->reviewRatings()->createMany($data['review_ratings']);

            // Update overall rating
            $avgRating = collect($data['review_ratings'])->avg('rating');
            $review->update(['rating' => $avgRating]);
        }

        return $review;
    }

    public function update(int|string $id, array $data): ?Review
    {
        $review = $this->find($id);

        if (!$review) {
            return null;
        }

        $review->update($data);

        if (!empty($data['review_ratings'])) {
            // We can choose to replace all ratings or update existing ones.
            // Replacing is safer for a full update payload.
            $review->reviewRatings()->delete();
            $review->reviewRatings()->createMany($data['review_ratings']);

            // Update overall rating
            $avgRating = collect($data['review_ratings'])->avg('rating');
            $review->update(['rating' => $avgRating]);
        }

        return $review;
    }

    public function getReviewsByOrderId(int $orderId): LengthAwarePaginator
    {
        return $this->model->where('order_id', $orderId)->with('reviewRatings.ratingKey')->paginate(15);
    }

    public function getReviewForStore(int $storeId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->whereHas('order', function ($query) use ($storeId) {
            $query->where('store_id', $storeId);
        })->with(['user', 'order', 'reviewRatings.ratingKey']);

        // Apply the search filter if provided
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('user', function ($subQ) use ($filters) {
                    $subQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $filters['search'] . '%']);
                })->orWhere('comment', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply the min_rating filter if provided
        if (isset($filters['min_rating'])) {
            // Filter based on the average rating (calculated from reviewRatings)
            $query->whereHas('reviewRatings', function ($query) use ($filters) {
                $query->havingRaw('AVG(rating) >= ?', [$filters['min_rating']]);
            });
        }

        // Apply the max_rating filter if provided
        if (isset($filters['max_rating'])) {
            // Filter based on the average rating (calculated from reviewRatings)
            $query->whereHas('reviewRatings', function ($query) use ($filters) {
                $query->havingRaw('AVG(rating) <= ?', [$filters['max_rating']]);
            });
        }

        // Apply date filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getReviewStatsForStore(int $storeId, array $filters = []): array
    {
        // Base query for stats (same filtering as getReviewForStore)
        $query = $this->model->whereHas('order', function ($query) use ($storeId) {
            $query->where('store_id', $storeId);
        });

        // Apply filters (excluding pagination related ones) to ensure stats reflect filtered view
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('user', function ($subQ) use ($filters) {
                    $subQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $filters['search'] . '%']);
                })->orWhere('comment', 'like', '%' . $filters['search'] . '%');
            });
        }
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (!empty($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }
        if (!empty($filters['max_rating'])) {
            $query->where('rating', '<=', $filters['max_rating']);
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $totalReviews = $query->count();
        $averageRating = $query->avg('rating');

        // Conditional counts disabled
        $repliedReviews = 0;
        $notRepliedReviews = $totalReviews;

        // Distribution
        $ratingDistribution = [
            '5_star' => (clone $query)->where('rating', '>=', 4.5)->count(),
            '4_star' => (clone $query)->whereBetween('rating', [3.5, 4.49])->count(),
            '3_star' => (clone $query)->whereBetween('rating', [2.5, 3.49])->count(),
            '2_star' => (clone $query)->whereBetween('rating', [1.5, 2.49])->count(),
            '1_star' => (clone $query)->where('rating', '<', 1.5)->count(),
        ];

        return [
            'average_rating' => round($averageRating ?? 0, 1),
            'total_reviews' => $totalReviews,
            'replied_reviews' => $repliedReviews,
            'not_replied_reviews' => $notRepliedReviews,
            'rating_distribution' => $ratingDistribution
        ];
    }
}
