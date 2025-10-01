<?php

namespace Modules\Store\Repositories;

use Modules\Store\Models\Store;
use Modules\Store\Repositories\Contracts\StoreRepositoryInterface;
use App\Repositories\BaseRepository;

class StoreRepository extends BaseRepository implements StoreRepositoryInterface
{
    public function __construct(Store $model)
    {
        parent::__construct($model);
    }
    public function getHomeStores(array $relations = [], array $filters = [])
    {
        $featured = $this->model->with($relations)->filter($filters)->whereIsFeatured(true)->latest()->limit(5)->get();

        $topReviews = $this->model
            ->with($relations)
            ->filter($filters)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('reviews_avg_rating')
            ->limit(10)
            ->get();

        $newStore = $this->model->with($relations)->filter($filters)->latest()->limit(5)->get();

        return [
            'topReviews' => $topReviews,
            'featured' => $featured,
            'newStores' => $newStore
        ];
    }
}
