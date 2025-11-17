<?php

namespace Modules\ShippingPrice\Repositories;
use Modules\ShippingPrice\Models\ShippingPrice;
use Modules\ShippingPrice\Repositories\Contracts\ShippingPriceRepositoryInterface;
use App\Repositories\BaseRepository;
class ShippingPriceRepository extends BaseRepository implements ShippingPriceRepositoryInterface
{
    public function __construct(ShippingPrice $model)
    {
        parent::__construct($model);
    }

    public function findByZoneAndVehicle(int $zoneId, int $vehicleId)
    {
        return $this->model->where('zone_id', $zoneId)->where('vehicle_id', $vehicleId)->first();
    }
}
