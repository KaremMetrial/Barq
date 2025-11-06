<?php

namespace Modules\Country\Services;

use Modules\Country\Models\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Country\Repositories\CountryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CountryService
{
    public function __construct(
        protected CountryRepository $countryRepository
    ) {}

    /**
     * Get all country codes with caching.
     */
    public function getAllCodes(): array
    {
        return Cache::rememberForever('countries.codes', function () {
            $countries = $this->countryRepository->all()->pluck('code')->toArray();
            return empty($countries) ? [] : $countries;
        });
    }

    /**
     * Clear cached countries.
     */
    public function clearCache(): void
    {
        Cache::forget('countries.codes');
    }

    public function getAllCountries($filters = [])
    {
        return $this->countryRepository->allWithTranslations($filters);
    }
    public function createCountry(array $data): ?Country
    {
        $this->clearCache();
        return $this->countryRepository->create($data);
    }

    public function getCountryById(int $id): ?Country
    {
        return $this->countryRepository->find($id);
    }

    public function updateCountry(int $id, array $data): ?Country
    {
        $this->clearCache();
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->countryRepository->update($id, $data);
    }

    public function deleteCountry(int $id): bool
    {
        $this->clearCache();
        return $this->countryRepository->delete($id);
    }
}
