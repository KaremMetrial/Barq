<?php

namespace Modules\Offer\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Offer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Offer\Repositories\OfferRepository;
use App\Enums\SaleTypeEnum;
use App\Helpers\CurrencyHelper;

class OfferService
{
    use FileUploadTrait;
    public function __construct(
        protected OfferRepository $OfferRepository
    ) {}

    public function getAllOffers(): Collection
    {
        return $this->OfferRepository->all();
    }

    public function createOffer(array $data): ?Offer
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));

            // Normalize fixed discount to minor units when possible
            if (!empty($data['discount_type']) && $data['discount_type'] == SaleTypeEnum::FIXED->value && isset($data['discount_amount'])) {
                $factor = $data['currency_factor'] ?? 100;
                $data['discount_amount_minor'] = CurrencyHelper::toMinorUnits((float)$data['discount_amount'], (int)$factor);
                $data['currency_factor'] = $factor;
                $data['currency_code'] = $data['currency_code'] ?? null;
            }

            return $this->OfferRepository->create($data);
        });
    }

    public function getOfferById(int $id): ?Offer
    {
        return $this->OfferRepository->find($id);
    }

    public function updateOffer(int $id, array $data): ?Offer
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));

            if (!empty($data['discount_type']) && $data['discount_type'] == SaleTypeEnum::FIXED->value && isset($data['discount_amount'])) {
                $factor = $data['currency_factor'] ?? 100;
                $data['discount_amount_minor'] = CurrencyHelper::toMinorUnits((float)$data['discount_amount'], (int)$factor);
                $data['currency_factor'] = $factor;
                $data['currency_code'] = $data['currency_code'] ?? null;
            }

            return $this->OfferRepository->update($id, $data);
        });

    }

    public function deleteOffer(int $id): bool
    {
        return $this->OfferRepository->delete($id);
    }
}
