<?php

namespace Modules\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Unit\Repositories\UnitRepository;
use Illuminate\Support\Facades\Cache;
use Modules\Unit\Models\Unit;
class UnitService
{
    public function __construct(
        protected UnitRepository $UnitRepository
    ) {}

    /**
     * Get all Unit codes with caching.
     */
    public function getAllCodes(): array
    {
        return Cache::rememberForever('Units.codes', function () {
            $Units = $this->UnitRepository->getAllCodes();
            return empty($Units) ? ['en'] : $Units;
        });
    }

    /**
     * Clear cached Units.
     */
    public function clearCache(): void
    {
        Cache::forget('Units.codes');
    }
    public function getAllUnits(): Collection
    {
        return $this->UnitRepository->all();
    }
    public function createUnit(array $data): ?Unit
    {
        $this->clearCache();
        return $this->UnitRepository->create($data);
    }
    public function getUnitById(int $id): ?Unit
    {
        return $this->UnitRepository->find($id);
    }
    public function updateUnit(int $id, array $data): ?Unit
    {
        $this->clearCache();
        return $this->UnitRepository->update($id, $data);
    }
    public function deleteUnit(int $id): bool
    {
        $this->clearCache();
        return $this->UnitRepository->delete($id);
    }
}
