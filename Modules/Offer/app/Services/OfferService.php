<?php

namespace Modules\Offer\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Offer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Offer\Repositories\OfferRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Enums\SaleTypeEnum;
use App\Helpers\CurrencyHelper;

class OfferService
{
    use FileUploadTrait;
    public function __construct(
        protected OfferRepository $OfferRepository
    ) {}

    public function getAllOffers(array $filters = [])
    {
        return $this->OfferRepository->paginate($filters);
    }

    /**
     * Resolve currency information from the offerable model
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    private function resolveCurrencyFromOfferable(array $data): array
    {
        // Load the offerable model to get currency information
        if (!isset($data['offerable_type']) || !isset($data['offerable_id'])) {
            throw new \InvalidArgumentException('Offerable type and ID are required');
        }

        $offerableClass = $this->resolveOfferableClass($data['offerable_type']);
        $offerableId = $data['offerable_id'];

        $offerable = $offerableClass::find($offerableId);
        if (!$offerable) {
            throw new \InvalidArgumentException('Offerable not found');
        }

        // Get currency information from the offerable's store
        $currencyInfo = $this->getCurrencyInfoFromOfferable($offerable);

        // Validate provided currency data against offerable's currency
        if (isset($data['currency_factor']) && $data['currency_factor'] != $currencyInfo['factor']) {
            throw new \InvalidArgumentException("Currency factor must match offerable's currency factor of {$currencyInfo['factor']}");
        }

        if (isset($data['currency_code']) && $data['currency_code'] != $currencyInfo['code']) {
            throw new \InvalidArgumentException("Currency code must match offerable's currency code of {$currencyInfo['code']}");
        }

        return [
            'currency_factor' => $currencyInfo['factor'],
            'currency_code' => $currencyInfo['code'],
        ];
    }

    /**
     * Resolve offerable class name from short name
     *
     * @param string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    private function resolveOfferableClass(string $type): string
    {
        // If it's already a full class name, use it directly
        if (class_exists($type)) {
            return $type;
        }

        // Use Laravel's morph map to resolve short names to full class names
        $morphMap = Relation::morphMap();
        if (isset($morphMap[$type])) {
            return $morphMap[$type];
        }

        throw new \InvalidArgumentException('Invalid offerable type: ' . $type);
    }

    /**
     * Get currency information from an offerable model
     *
     * @param mixed $offerable
     * @return array
     */
    private function getCurrencyInfoFromOfferable($offerable): array
    {
        // If offerable has a store relationship (like Product), get currency from store
        if (method_exists($offerable, 'store') && $offerable->store) {
            return [
                'factor' => $offerable->store->getCurrencyFactor(),
                'code' => $offerable->store->currency_code ?? $offerable->store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP',
            ];
        }

        // If offerable is a Store itself
        if (method_exists($offerable, 'getCurrencyFactor')) {
            return [
                'factor' => $offerable->getCurrencyFactor(),
                'code' => $offerable->currency_code ?? $offerable->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP',
            ];
        }

        // Fallback to default currency
        return [
            'factor' => 100,
            'code' => 'EGP',
        ];
    }

    public function createOffer(array $data): ?Offer
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));

            // Resolve currency information from offerable
            $currencyInfo = $this->resolveCurrencyFromOfferable($data);

            // Convert discount amount to minor units
            if (isset($data['discount_amount'])) {
                $data['discount_amount'] = CurrencyHelper::toMinorUnits( $data['discount_amount'], $currencyInfo['currency_factor']);
            }

            // Set currency information
            $data['currency_factor'] = $currencyInfo['currency_factor'];
            $data['currency_code'] = $currencyInfo['currency_code'];

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

            $offer = $this->OfferRepository->find($id);
            if (!$offer) {
                throw new \InvalidArgumentException('Offer not found');
            }

            // If offerable is being changed, resolve currency from new offerable
            if (isset($data['offerable_type']) || isset($data['offerable_id'])) {
                $currencyInfo = $this->resolveCurrencyFromOfferable(array_merge($offer->toArray(), $data));
            } else {
                // Use existing currency info
                $currencyInfo = [
                    'currency_factor' => $offer->currency_factor ?? 100,
                    'currency_code' => $offer->currency_code ?? 'EGP',
                ];
            }

            // Convert discount amount to minor units if provided
            if (isset($data['discount_amount'])) {
                $data['discount_amount'] = CurrencyHelper::toMinorUnits( $data['discount_amount'], $currencyInfo['currency_factor']);
            }

            // Set currency information
            $data['currency_factor'] = $currencyInfo['currency_factor'];
            $data['currency_code'] = $currencyInfo['currency_code'];

            return $this->OfferRepository->update($id, $data);
        });
    }

    public function deleteOffer(int $id): bool
    {
        return $this->OfferRepository->delete($id);
    }
    public function toggleStatus(int $id): ?Offer
    {
        return DB::transaction(function () use ($id) {
            $offer = $this->OfferRepository->find($id);
            if (!$offer) {
                throw new \InvalidArgumentException('Offer not found');
            }

            $offer->is_active = !$offer->is_active;
            $offer->save();

            return $offer->refresh();
        });
    }
}
