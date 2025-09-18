<?php

namespace Modules\City\Services;

use Illuminate\Support\Facades\DB;
use Modules\City\Models\City;
use Illuminate\Database\Eloquent\Collection;
use Modules\City\Repositories\CityRepository;

class CityService
{
    public function __construct(
        protected CityRepository $CityRepository
    ) {}

    public function getAllCitys(): Collection
    {
        return $this->CityRepository->all();
    }

    public function createCity(array $data): ?City
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->CityRepository->create($data);
        });
    }

    public function getCityById(int $id): ?City
    {
        return $this->CityRepository->find($id);
    }

    public function updateCity(int $id, array $data): ?City
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->CityRepository->update($id, $data);
        });
    }

    public function deleteCity(int $id): bool
    {
        return $this->CityRepository->delete($id);
    }
}
