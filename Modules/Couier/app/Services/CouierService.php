<?php

namespace Modules\Couier\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Couier\Models\Couier;
use Modules\Couier\Repositories\CouierRepository;
use Illuminate\Support\Facades\Cache;

class CouierService
{
    use FileUploadTrait;
    public function __construct(
        protected CouierRepository $CouierRepository
    ) {}

    public function getAllCouiers(): Collection
    {
        return $this->CouierRepository->all();
    }

    public function createCouier(array $data): ?Couier
    {
        if (request()->hasFile('avatar')) {
            $data['avatar'] = $this->upload(
                request(),
                'avatar',
                'uploads/avatars',
                'public'
            );        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->CouierRepository->create($data)->refresh();
    }

    public function getCouierById(int $id)
    {
        return $this->CouierRepository->find($id);
    }

    public function updateCouier(int $id, array $data)
    {
        if (request()->hasFile('avatar')) {
            $data['avatar'] = $this->upload(
                request(),
                'avatar',
                'uploads/avatars',
                'public'
            );        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->CouierRepository->update($id, $data)->refresh();
    }

    public function deleteCouier(int $id): bool
    {
        return $this->CouierRepository->delete($id);
    }
}
