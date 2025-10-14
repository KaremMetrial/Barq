<?php

namespace Modules\Zone\Services;

use Illuminate\Support\Facades\DB;
use Modules\Zone\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Modules\Zone\Repositories\ZoneRepository;

class ZoneService
{
    public function __construct(
        protected ZoneRepository $ZoneRepository
    ) {}

    public function getAllZones($filters = []): Collection
    {
        return $this->ZoneRepository->allWithTranslations($filters);;
    }

    public function createZone(array $data): ?Zone
    {
        return DB::transaction(function () use ($data) {
            if (!empty($data['area'])) {
                $data['area'] = DB::raw("ST_GeomFromText('{$data['area']}')");
            }
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->ZoneRepository->create($data);
        });
    }

    public function getZoneById(int $id): ?Zone
    {
        return $this->ZoneRepository->find($id);
    }

    public function updateZone(int $id, array $data): ?Zone
    {
        return DB::transaction(function () use ($data, $id) {
            if (!empty($data['area'])) {
                $data['area'] = DB::raw("ST_GeomFromText('{$data['area']}')");
            }

            $data = array_filter($data, fn($value) => !blank($value));
            return $this->ZoneRepository->update($id, $data);
        });
    }

    public function deleteZone(int $id): bool
    {
        return $this->ZoneRepository->delete($id);
    }
}
