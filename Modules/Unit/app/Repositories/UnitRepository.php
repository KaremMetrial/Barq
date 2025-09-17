<?php

namespace Modules\Unit\Repositories;
use Modules\Unit\Models\Unit;
use Modules\Unit\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\BaseRepository;
class UnitRepository extends BaseRepository implements UnitRepositoryInterface
{
    public function __construct(Unit $model)
    {
        parent::__construct($model);
    }
    public function getAllCodes(): array
    {
        return $this->model->pluck('code')->toArray();
    }
}
