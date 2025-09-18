<?php

namespace Modules\Offer\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Offer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Offer\Repositories\OfferRepository;

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
            return $this->OfferRepository->update($id, $data);
        });

    }

    public function deleteOffer(int $id): bool
    {
        return $this->OfferRepository->delete($id);
    }
}
