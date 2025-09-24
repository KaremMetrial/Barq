<?php

namespace Modules\Interest\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Interest\Models\Interest;
use Modules\Interest\Repositories\InterestRepository;
use Illuminate\Support\Facades\Cache;

class InterestService
{
    use FileUploadTrait;
    public function __construct(
        protected InterestRepository $InterestRepository
    ) {}

    public function getAllInterests(): Collection
    {
        return $this->InterestRepository->all();
    }

    public function createInterest(array $data): ?Interest
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('interests', 'public');
        }
        $data = array_filter($data, fn($value) => !is_null($value));
        return $this->InterestRepository->create($data);
    }

    public function getInterestById(int $id): ?Interest
    {
        return $this->InterestRepository->find($id);
    }

    public function updateInterest(int $id, array $data): ?Interest
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('interests', 'public');
        }
        $data = array_filter($data, fn($value) => !is_null($value));
        return $this->InterestRepository->update($id, $data);
    }

    public function deleteInterest(int $id): bool
    {
        return $this->InterestRepository->delete($id);
    }
}
