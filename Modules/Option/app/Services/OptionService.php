<?php

namespace Modules\Option\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Option\Models\Option;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Option\Repositories\OptionRepository;

class OptionService
{
    use FileUploadTrait;
    public function __construct(
        protected OptionRepository $OptionRepository
    ) {}

    public function getAllOptions(): Collection
    {
        return $this->OptionRepository->all();
    }

    public function createOption(array $data): ?Option
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->OptionRepository->create($data);
        });
    }

    public function getOptionById(int $id): ?Option
    {
        return $this->OptionRepository->find($id);
    }

    public function updateOption(int $id, array $data): ?Option
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->OptionRepository->update($id, $data);
        });

    }

    public function deleteOption(int $id): bool
    {
        return $this->OptionRepository->delete($id);
    }
}
