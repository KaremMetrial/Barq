<?php

namespace Modules\Favourite\Services;

use Modules\Store\Models\Store;
use Illuminate\Support\Facades\DB;
use Modules\Favourite\Models\Favourite;
use Illuminate\Database\Eloquent\Collection;
use Modules\Favourite\Repositories\FavouriteRepository;

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

        $sectionsWithStores = $groupedFavourites->map(function ($items, $sectionName) {
            return [
                'section_name' => $sectionName,
                'stores' => $items->map(function ($item) {
                    return [
                        'store_id' => $item->favouriteable->id,
                        'store_name' => $item->favouriteable->name,
                        'store_logo' => $item->favouriteable->logo ? asset('storage/' . $item->favouriteable->logo) : null,
                        'note' => $item->favouriteable->note,
                        'cover_image' => $item->favouriteable->cover_image ? asset('storage/' . $item->favouriteable->cover_image) : null,
                        'phone' => $item->favouriteable->phone,
                        'avg_rate' => $item->favouriteable->avg_rate,
                        'getDeliveryFee' => $item->favouriteable->getDeliveryFee() ?? 0,
                    ];
                })
            ];
        });

        return $sectionsWithStores;
    }



    public function createFavourite(array $data): ?Favourite
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->favouriteRepository->create($data);
        });
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
