<?php

namespace Modules\Favourite\Services;

use Modules\Store\Models\Store;
use Illuminate\Support\Facades\DB;
use Modules\Favourite\Models\Favourite;
use Illuminate\Database\Eloquent\Collection;
use Modules\Favourite\Repositories\FavouriteRepository;
use Modules\Store\Http\Resources\StoreResource;

class FavouriteService
{
    public function __construct(
        protected FavouriteRepository $favouriteRepository
    ) {}

    public function getAllFavourites()
    {
        $favourites = Favourite::with(['favouriteable', 'favouriteable.section'])
            ->where('favouriteable_type', 'store')
            ->where('user_id', auth('user')->id())
            ->get();

        $groupedFavourites = $favourites->groupBy(function ($item) {
            if ($item->favouriteable instanceof Store && $item->favouriteable->section) {
                return $item->favouriteable->section->name;
            }

            return 'Uncategorized';
        });

        $sections = $groupedFavourites->keys()->toArray();

        $sectionsWithStores = $groupedFavourites->map(function ($items, $sectionName) {
            return [
                'section_name' => $sectionName,
                'section_type' => $items->first()->favouriteable->section->type->value ?? null,
                'stores' => StoreResource::collection($items->pluck('favouriteable'))
            ];
        })->values()->toArray();

        return [
            'sections' => $sections,
            'sections_with_stores' => $sectionsWithStores
        ];
    }


    public function toggleFavourite(array $data): bool
    {
        $data = array_filter($data, fn($value) => !blank($value));

        $userId = $data['user_id'] ?? auth('user')->id();
        $favouriteableId = $data['favouriteable_id'] ?? null;
        $favouriteableType = $data['favouriteable_type'] ?? null;

        if (!$userId || !$favouriteableId || !$favouriteableType) {
            return false;
        }

        $query = Favourite::query()
            ->where('user_id', $userId)
            ->where('favouriteable_id', $favouriteableId)
            ->where('favouriteable_type', $favouriteableType);

        if ($query->exists()) {
            $query->delete();
            return false;
        }

        $this->favouriteRepository->create($data);
        return true;
    }

    public function getFavouriteById(int $id): ?Favourite
    {
        return $this->favouriteRepository->find($id);
    }

    public function deleteFavourite(int $id): bool
    {
        return $this->favouriteRepository->delete($id);
    }
}
