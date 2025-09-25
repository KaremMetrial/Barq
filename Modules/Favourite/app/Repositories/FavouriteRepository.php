<?php

namespace Modules\Favourite\Repositories;
use Modules\Favourite\Models\Favourite;
use Modules\Favourite\Repositories\Contracts\FavouriteRepositoryInterface;
use App\Repositories\BaseRepository;
class FavouriteRepository extends BaseRepository implements FavouriteRepositoryInterface
{
    public function __construct(Favourite $model)
    {
        parent::__construct($model);
    }
}
