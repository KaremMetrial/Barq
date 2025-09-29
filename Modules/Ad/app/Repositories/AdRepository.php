<?php

namespace Modules\Ad\Repositories;
use Modules\Ad\Models\Ad;
use Modules\Ad\Repositories\Contracts\AdRepositoryInterface;
use App\Repositories\BaseRepository;
class AdRepository extends BaseRepository implements AdRepositoryInterface
{
    public function __construct(Ad $model)
    {
        parent::__construct($model);
    }
}
