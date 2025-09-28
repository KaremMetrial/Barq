<?php

namespace Modules\AddOn\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\AddOn\Models\AddOn;
use Modules\AddOn\Repositories\AddOnRepository;
use Illuminate\Support\Facades\Cache;

class AddOnService
{
    public function __construct(
        protected AddOnRepository $AddOnRepository
    ) {}

    public function getAllAddOns(): Collection
    {
        return $this->AddOnRepository->all();
    }

    public function createAddOn(array $data): ?AddOn
    {
        return $this->AddOnRepository->create($data);
    }

    public function getAddOnById(int $id): ?AddOn
    {
        return $this->AddOnRepository->find($id);
    }

    public function updateAddOn(int $id, array $data): ?AddOn
    {
        return $this->AddOnRepository->update($id, $data);
    }

    public function deleteAddOn(int $id): bool
    {
        return $this->AddOnRepository->delete($id);
    }
}
