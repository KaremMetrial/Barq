<?php

namespace Modules\Product\Repositories;

use Modules\Product\Models\Product;
use Modules\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\BaseRepository;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
    public function home(array $relations = [])
    {
        $featured = $this->model->with($relations)->whereIsFeatured(true)->whereIsActive(true)->latest()->limit(5)->get();

        $topReviews = $this->model
            ->with($relations)
            ->whereIsActive(true)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('reviews_avg_rating')
            ->limit(10)
            ->get();

        $newProduct = $this->model->with($relations)->whereIsActive(true)->latest()->limit(5)->get();

        return [
            'topReviews' => $topReviews,
            'featured' => $featured,
            'newProduct' => $newProduct
        ];
    }

    public function getStats(int $productId): array
    {
        $product = $this->model->findOrFail($productId);
        return $product->getStats();
    }
    public function toggleActive(int $productId)
    {
        $product = $this->model->findOrFail($productId);
        return $product->toggleActive();
    }
}
