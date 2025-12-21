<?php

namespace Modules\AddOn\Services;

use App\Helpers\CurrencyHelper;
use Illuminate\Database\Eloquent\Collection;
use Modules\AddOn\Models\AddOn;
use Modules\AddOn\Repositories\AddOnRepository;
use Illuminate\Support\Facades\Cache;
use Modules\Store\Repositories\StoreRepository;

class AddOnService
{
    public function __construct(
        protected AddOnRepository $AddOnRepository,
        protected StoreRepository $storeRepository
    ) {}

    public function getAllAddOns(): Collection
    {
        return $this->AddOnRepository->all();
    }

    public function createAddOn(array $data): ?AddOn
    {
        if (isset($data['price'])) {
            $factor = 100;
            if (isset($data['store_id'])) {
                $store = $this->storeRepository->find($data['store_id']);
                if ($store) {
                    $factor = $store->getCurrencyFactor();
                }
            }
            $data['price'] = CurrencyHelper::priceToUnsignedBigInt($data['price'], $factor);
        }
        return $this->AddOnRepository->create($data);
    }

    public function getAddOnById(int $id): ?AddOn
    {
        return $this->AddOnRepository->find($id);
    }

    public function updateAddOn(int $id, array $data): ?AddOn
    {
        if (isset($data['price'])) {
            $factor = 100;
            $addOn = $this->AddOnRepository->find($id);
            if ($addOn && $addOn->store_id) {
                $store = $this->storeRepository->find($addOn->store_id);
                if ($store) {
                    $factor = $store->getCurrencyFactor();
                }
            }
            $data['price'] = CurrencyHelper::priceToUnsignedBigInt($data['price'], $factor);
        }
        return $this->AddOnRepository->update($id, $data);
    }

    public function deleteAddOn(int $id): bool
    {
        return $this->AddOnRepository->delete($id);
    }
}
