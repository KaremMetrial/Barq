<?php

namespace Modules\Country\Repositories;
use Modules\Country\Models\Country;
use Modules\Country\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\BaseRepository;
class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }
}
