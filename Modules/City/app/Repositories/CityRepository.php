<?php

namespace Modules\City\Repositories;
use Modules\City\Models\City;
use Modules\City\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\BaseRepository;
class CityRepository extends BaseRepository implements CityRepositoryInterface
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }
}
