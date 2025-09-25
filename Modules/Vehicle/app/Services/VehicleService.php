<?php

namespace Modules\Vehicle\Services;

use Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Vehicle\Repositories\VehicleRepository;

class VehicleService
{
    public function __construct(
        protected VehicleRepository $VehicleRepository
    ) {}

    public function getAllVehicles(): Collection
    {
        return $this->VehicleRepository->all();
    }
    public function createVehicle(array $data): ?Vehicle
    {
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->VehicleRepository->create($data)->refresh();
    }
    public function getVehicleById(int $id)
    {
        return $this->VehicleRepository->find($id);
    }
    public function updateVehicle(int $id, array $data)
    {
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->VehicleRepository->update($id, $data)->refresh();
    }
    public function deleteVehicle(int $id): bool
    {
        return $this->VehicleRepository->delete($id);
    }
}
