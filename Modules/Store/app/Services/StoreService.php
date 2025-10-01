<?php

namespace Modules\Store\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Store\Repositories\StoreRepository;

class StoreService
{
    use FileUploadTrait;
    public function __construct(
        protected StoreRepository $StoreRepository
    ) {}

    public function getAllStores()
    {
        return $this->StoreRepository->paginate([],15,['section.categories','storeSetting']);
    }

    public function createStore(array $data): ?Store
    {
        return DB::transaction(function () use ($data) {
            $data['logo'] = $this->upload(
                request(),
                'logo',
                'uploads/logos',
                'public'
            );
            $data['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreRepository->create($data);
        });
    }

    public function getStoreById(int $id): ?Store
    {
        return $this->StoreRepository->find($id, ['section.categories','storeSetting']);
    }

    public function updateStore(int $id, array $data): ?Store
    {
        return DB::transaction(function () use ($data, $id) {
            $data['logo'] = $this->upload(
                request(),
                'logo',
                'uploads/logos',
                'public'
            );
            $data['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreRepository->update($id, $data);
        });

    }

    public function deleteStore(int $id): bool
    {
        return $this->StoreRepository->delete($id);
    }
    public function getHomeStores(array $filters = []): array
    {
        $relation = ['section', 'section.categories', 'StoreSetting'];
        return $this->StoreRepository->getHomeStores($relation, $filters);
    }
}
