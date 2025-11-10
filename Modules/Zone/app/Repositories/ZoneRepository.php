<?php

namespace Modules\Zone\Repositories;
use Modules\Zone\Models\Zone;
use Modules\Zone\Repositories\Contracts\ZoneRepositoryInterface;
use App\Repositories\BaseRepository;
class ZoneRepository extends BaseRepository implements ZoneRepositoryInterface
{
    public function __construct(Zone $model)
    {
        parent::__construct($model);
    }
    public function findByLatLong($lat, $long)
    {
        return $this->model->findZoneByCoordinates($lat, $long);
    }
}
