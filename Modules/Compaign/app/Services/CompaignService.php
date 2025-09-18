<?php

namespace Modules\Compaign\Services;

use Illuminate\Support\Facades\DB;
use Modules\Compaign\Models\Compaign;
use Illuminate\Database\Eloquent\Collection;
use Modules\Compaign\Repositories\CompaignRepository;

class CompaignService
{
    public function __construct(
        protected CompaignRepository $compaignRepository
    ) {}

    public function getAllCompaigns(): Collection
    {
        return $this->compaignRepository->all();
    }

    public function createCompaign(array $data): ?Compaign
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->compaignRepository->create($data);
        });
    }

    public function getCompaignById(int $id): ?Compaign
    {
        return $this->compaignRepository->find($id);
    }

    public function updateCompaign(int $id, array $data): ?Compaign
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->compaignRepository->update($id, $data);
        });
    }

    public function deleteCompaign(int $id): bool
    {
        return $this->compaignRepository->delete($id);
    }
}
