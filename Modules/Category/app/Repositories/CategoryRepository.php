<?php

namespace Modules\Category\Repositories;
use Modules\Category\Models\Category;
use Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\BaseRepository;
class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }
}
