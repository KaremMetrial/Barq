<?php

namespace Modules\ShippingPrice\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\ShippingPrice\Models\ShippingPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\ShippingPrice\Repositories\ShippingPriceRepository;

class ShippingPriceService
{
    use FileUploadTrait;
    public function __construct(
        protected ShippingPriceRepository $ShippingPriceRepository
    ) {}

    public function getAllShippingPrices($filters = []): Collection
    {
        return $this->ShippingPriceRepository->allWithTranslations($filters);
    }

    public function createShippingPrice(array $data): ?ShippingPrice
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            $ShippingPrice = $this->ShippingPriceRepository->create($data);
            return $ShippingPrice->refresh();
        });
    }

    public function getShippingPriceById(int $id): ?ShippingPrice
    {
        return $this->ShippingPriceRepository->find($id);
    }

    public function updateShippingPrice(int $id, array $data): ?ShippingPrice
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            $ShippingPrice = $this->ShippingPriceRepository->find($id);
            $ShippingPrice->update($data);
            return $ShippingPrice->refresh();
        });
    }

    public function deleteShippingPrice(int $id): bool
    {
        return $this->ShippingPriceRepository->delete($id);
    }
}
