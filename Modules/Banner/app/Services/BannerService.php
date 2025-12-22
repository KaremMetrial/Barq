<?php

namespace Modules\Banner\Services;

use App\Traits\FileUploadTrait;
use Carbon\Carbon;
use Modules\Banner\Models\Banner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Banner\Repositories\BannerRepository;

class BannerService
{
    use FileUploadTrait;
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
            $data['image'] = $this->upload(request(), 'image', 'uploads/image/banners','public');
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
            $data['image'] = $this->upload(request(), 'image', 'uploads/image/banners','public');
        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->BannerRepository->update($id, $data);
    }

    public function deleteBanner(int $id): bool
    {
        return $this->BannerRepository->delete($id);
    }
    public function getIndex()
    {
        $today = Carbon::today();

        $banners = Banner::where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->inRandomOrder()
            ->take(3)
            ->get();
        return $banners;
    }
}
