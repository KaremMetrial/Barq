<?php

namespace Modules\Governorate\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Governorate\Models\Governorate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Governorate\Repositories\GovernorateRepository;

class GovernorateService
{
    use FileUploadTrait;
    public function __construct(
        protected GovernorateRepository $GovernorateRepository
    ) {}

    public function getAllGovernorates(): Collection
    {
        return $this->GovernorateRepository->allWithTranslations();
    }

    public function createGovernorate(array $data): ?Governorate
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->GovernorateRepository->create($data);
        });
    }

    public function getGovernorateById(int $id): ?Governorate
    {
        return $this->GovernorateRepository->find($id);
    }

    public function updateGovernorate(int $id, array $data): ?Governorate
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->GovernorateRepository->update($id, $data);
        });

    }

    public function deleteGovernorate(int $id): bool
    {
        return $this->GovernorateRepository->delete($id);
    }
}
