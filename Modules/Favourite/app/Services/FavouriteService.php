<?php

namespace Modules\Favourite\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Favourite\Models\Favourite;
use Modules\Favourite\Repositories\FavouriteRepository;
use Illuminate\Support\Facades\Cache;

class FavouriteService
{
    use FileUploadTrait;
    public function __construct(
        protected FavouriteRepository $FavouriteRepository
    ) {}

    public function getAllFavourites(): Collection
    {
        return $this->FavouriteRepository->all();
    }

    public function createFavourite(array $data): ?Favourite
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('Favourites', 'public');
        }
        $data = array_filter($data, fn($value) => !is_null($value));
        return $this->FavouriteRepository->create($data);
    }

    public function getFavouriteById(int $id): ?Favourite
    {
        return $this->FavouriteRepository->find($id);
    }

    public function updateFavourite(int $id, array $data): ?Favourite
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('Favourites', 'public');
        }
        $data = array_filter($data, fn($value) => !is_null($value));
        return $this->FavouriteRepository->update($id, $data);
    }

    public function deleteFavourite(int $id): bool
    {
        return $this->FavouriteRepository->delete($id);
    }
}
