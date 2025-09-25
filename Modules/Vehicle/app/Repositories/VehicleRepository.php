<?php

namespace Modules\Vehicle\Repositories;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;
use App\Repositories\BaseRepository;
class VehicleRepository extends BaseRepository implements VehicleRepositoryInterface
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }
}
