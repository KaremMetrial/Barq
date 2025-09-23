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
}
