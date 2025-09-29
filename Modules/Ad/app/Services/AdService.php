<?php

namespace Modules\Ad\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Ad\Models\Ad;
use Modules\Ad\Repositories\AdRepository;
use Illuminate\Support\Facades\Cache;

class AdService
{
    use FileUploadTrait;
    public function __construct(
        protected AdRepository $AdRepository
    ) {}

    public function getAllAds(): Collection
    {
        return $this->AdRepository->all();
    }

    public function createAd(array $data): ?Ad
    {
        if (request()->hasFile('media_path')) {
            $data['media_path'] = $this->upload(
                request(),
                'media_path',
                'uploads/ads',
                'public'
            );
        }
        $data['ad_number'] = 'AD' . strtoupper(uniqid());
        $data['adable_type'] = $data['adable_type'] ?? 'admin';
        $data['adable_id'] = $data['adable_id'] ?? auth('admin')->id();
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->AdRepository->create($data)->fresh();
    }

    public function getAdById(int $id): ?Ad
    {
        return $this->AdRepository->find($id);
    }

    public function updateAd(int $id, array $data): ?Ad
    {
        return $this->AdRepository->update($id, $data);
    }

    public function deleteAd(int $id): bool
    {
        return $this->AdRepository->delete($id);
    }
}
