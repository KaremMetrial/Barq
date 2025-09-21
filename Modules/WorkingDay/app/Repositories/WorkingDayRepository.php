<?php

namespace Modules\WorkingDay\Repositories;
use Modules\WorkingDay\Models\WorkingDay;
use Modules\WorkingDay\Repositories\Contracts\WorkingDayRepositoryInterface;
use App\Repositories\BaseRepository;
class WorkingDayRepository extends BaseRepository implements WorkingDayRepositoryInterface
{
    public function __construct(WorkingDay $model)
    {
        parent::__construct($model);
    }
}
