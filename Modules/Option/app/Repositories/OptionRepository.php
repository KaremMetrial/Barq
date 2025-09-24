<?php

namespace Modules\Option\Repositories;
use Modules\Option\Models\Option;
use Modules\Option\Repositories\Contracts\OptionRepositoryInterface;
use App\Repositories\BaseRepository;
class OptionRepository extends BaseRepository implements OptionRepositoryInterface
{
    public function __construct(Option $model)
    {
        parent::__construct($model);
    }
}
