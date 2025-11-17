<?php

namespace Modules\ShippingPrice\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\ShippingPrice\Models\ShippingPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\ShippingPrice\Repositories\ShippingPriceRepository;

class ShippingPriceService
{
    use FileUploadTrait;
    public function __construct(
        protected ShippingPriceRepository $ShippingPriceRepository
    ) {}

    public function getAllShippingPrices($filters = []): Collection
    {
        return $this->ShippingPriceRepository->allWithTranslations($filters);
    }

    public function createShippingPrice(array $data): ?ShippingPrice
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            $ShippingPrice = $this->ShippingPriceRepository->create($data);
            return $ShippingPrice->refresh();
        });
    }

    public function createMultipleShippingPrices(array $shippingPricesData): array
    {
        $createdPrices = [];
        foreach ($shippingPricesData as $data) {
            $createdPrices[] = $this->createShippingPrice($data);
        }
        return $createdPrices;
    }

    public function getShippingPriceById(int $id): ?ShippingPrice
    {
        return $this->ShippingPriceRepository->find($id);
    }

    public function updateShippingPrice(int $id, array $data): ?ShippingPrice
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            $ShippingPrice = $this->ShippingPriceRepository->find($id);
            $ShippingPrice->update($data);
            return $ShippingPrice->refresh();
        });
    }

    public function deleteShippingPrice(int $id): bool
    {
        return $this->ShippingPriceRepository->delete($id);
    }

    public function updateMultipleShippingPrices(int $zoneId, array $vehiclesData): array
    {
        $updatedPrices = [];
        foreach ($vehiclesData as $vehicleData) {
            $vehicleId = $vehicleData['vehicle_id'];
            $shippingPrice = $this->ShippingPriceRepository->findByZoneAndVehicle($zoneId, $vehicleId);

            if ($shippingPrice) {
                // Update existing
                $data = array_filter($vehicleData, fn($value) => !blank($value));
                unset($data['vehicle_id']); // Remove vehicle_id from data
                $shippingPrice->update($data);
                $updatedPrices[] = $shippingPrice->refresh();
            } else {
                // Create new if not exists
                $zone = \Modules\Zone\Models\Zone::findOrFail($zoneId);
                $vehicle = \Modules\Vehicle\Models\Vehicle::findOrFail($vehicleId);
                $data = array_filter($vehicleData, fn($value) => !blank($value));
                $data['name'] = $zone->name . ' - ' . $vehicle->name;
                $data['zone_id'] = $zoneId;
                unset($data['vehicle_id']);
                $newPrice = $this->ShippingPriceRepository->create($data);
                $updatedPrices[] = $newPrice->refresh();
            }
        }
        return $updatedPrices;
    }
}
