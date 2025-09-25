<?php

namespace Modules\Banner\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Banner\Models\Banner;
use Modules\Banner\Repositories\BannerRepository;
use Illuminate\Support\Facades\Cache;

class BannerService
{
    public function __construct(
        protected BannerRepository $BannerRepository
    ) {}

    public function getAllBanners(): Collection
    {
        return $this->BannerRepository->all();
    }

    public function createBanner(array $data): ?Banner
    {
        if (request()->hasFile('image')) {
            $data['image'] = request()->file('image')->store('uploads/image', 'public');
        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->BannerRepository->create($data);
    }

    public function getBannerById(int $id): ?Banner
    {
        return $this->BannerRepository->find($id);
    }

    public function updateBanner(int $id, array $data): ?Banner
    {
        if (request()->hasFile('image')) {
            $data['image'] = request()->file('image')->store('uploads/image', 'public');
        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->BannerRepository->update($id, $data);
    }

    public function deleteBanner(int $id): bool
    {
        return $this->BannerRepository->delete($id);
    }
}
