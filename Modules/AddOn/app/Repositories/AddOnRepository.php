<?php

namespace Modules\AddOn\Repositories;
use Modules\AddOn\Models\AddOn;
use Modules\AddOn\Repositories\Contracts\AddOnRepositoryInterface;
use App\Repositories\BaseRepository;
class AddOnRepository extends BaseRepository implements AddOnRepositoryInterface
{
    public function __construct(AddOn $model)
    {
        parent::__construct($model);
    }
}
