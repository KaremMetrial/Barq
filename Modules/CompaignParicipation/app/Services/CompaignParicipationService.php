<?php

namespace Modules\CompaignParicipation\Services;

use Illuminate\Support\Facades\DB;
use Modules\CompaignParicipation\Models\CompaignParicipation;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompaignParicipation\Repositories\CompaignParicipationRepository;

class CompaignParicipationService
{
    public function __construct(
        protected CompaignParicipationRepository $CompaignParicipationRepository
    ) {}

    public function getAllCompaignParicipations(): Collection
    {
        return $this->CompaignParicipationRepository->all();
    }

    public function createCompaignParicipation(array $data): ?CompaignParicipation
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->CompaignParicipationRepository->create($data);
        });
    }

    public function getCompaignParicipationById(int $id): ?CompaignParicipation
    {
        return $this->CompaignParicipationRepository->find($id);
    }

    public function updateCompaignParicipation(int $id, array $data): ?CompaignParicipation
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->CompaignParicipationRepository->update($id, $data);
        });
    }

    public function deleteCompaignParicipation(int $id): bool
    {
        return $this->CompaignParicipationRepository->delete($id);
    }
}
