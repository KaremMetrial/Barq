<?php

namespace Modules\Governorate\Repositories;
use Modules\Governorate\Models\Governorate;
use Modules\Governorate\Repositories\Contracts\GovernorateRepositoryInterface;
use App\Repositories\BaseRepository;
class GovernorateRepository extends BaseRepository implements GovernorateRepositoryInterface
{
    public function __construct(Governorate $model)
    {
        parent::__construct($model);
    }
}
