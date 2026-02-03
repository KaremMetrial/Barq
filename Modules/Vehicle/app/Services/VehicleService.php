<?php

namespace Modules\Vehicle\Services;

use App\Traits\FileUploadTrait;
use Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Vehicle\Repositories\VehicleRepository;

class VehicleService
{
    use FileUploadTrait;
    public function __construct(
        protected VehicleRepository $VehicleRepository
    ) {}

    public function getAllVehicles()
    {
        return $this->VehicleRepository->allWithTranslations();
    }
    public function createVehicle(array $data): ?Vehicle
    {
        $data['icon'] = $this->upload(request(), 'icon', 'icons/vehicles', 'public', [512,512]);
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->VehicleRepository->create($data)->refresh();
    }
    public function getVehicleById(int $id)
    {
        return $this->VehicleRepository->find($id);
    }
    public function updateVehicle(int $id, array $data)
    {
        $data['icon'] = $this->upload(request(), 'icon', 'icons/vehicles', 'public', [512,512]);
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->VehicleRepository->update($id, $data)->refresh();
    }
    public function deleteVehicle(int $id): bool
    {
        return $this->VehicleRepository->delete($id);
    }
}
