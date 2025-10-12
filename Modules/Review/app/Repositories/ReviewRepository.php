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
}
