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
    public function getReviewsByOrderId(int $orderId): LengthAwarePaginator
    {
        return $this->model->where('order_id', $orderId)->paginate(15);
    }
    public function getReviewForStore(int $storeId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->whereHas('order', function ($query) use ($storeId) {
            $query->where('store_id', $storeId);
        });
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('user', function ($subQ) use ($filters) {
                    $subQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $filters['search'] . '%']);
                })->orWhere('comment', 'like', '%' . $filters['search'] . '%');
            });
        }
        // Apply filters
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['replied'])) {
            $query->where('response', $filters['replied'] ? '!=' : '=', null);
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

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
