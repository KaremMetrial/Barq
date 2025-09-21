<?php

namespace Modules\StoreSetting\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\StoreSetting\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\StoreSetting\Repositories\StoreSettingRepository;

class StoreSettingService
{
    use FileUploadTrait;
    public function __construct(
        protected StoreSettingRepository $StoreSettingRepository
    ) {}

    public function getAllStoreSettings(): Collection
    {
        return $this->StoreSettingRepository->all();
    }

    public function createStoreSetting(array $data): ?StoreSetting
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreSettingRepository->create($data);
        });
    }

    public function getStoreSettingById(int $id): ?StoreSetting
    {
        return $this->StoreSettingRepository->find($id);
    }

    public function updateStoreSetting(int $id, array $data): ?StoreSetting
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreSettingRepository->update($id, $data);
        });

    }

    public function deleteStoreSetting(int $id): bool
    {
        return $this->StoreSettingRepository->delete($id);
    }
}
