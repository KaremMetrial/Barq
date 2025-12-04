<?php

namespace Modules\Address\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\AddressRepository;
use Illuminate\Support\Facades\Cache;
use Modules\Zone\Repositories\ZoneRepository;
use Illuminate\Support\Facades\Log;
class AddressService
{
    public function __construct(
        protected AddressRepository $AddressRepository, protected ZoneRepository $zoneRepository
    ) {}

    public function getAllAddresses($filters = []): Collection
    {
        return $this->AddressRepository->all(['zone'], ['*'], $filters);
    }

    public function createAddress(array $data): ?Address
    {
        return $this->AddressRepository->create($data);
    }

    public function getAddressById(int $id): ?Address
    {
        return $this->AddressRepository->find($id, ['zone']);
    }

    public function updateAddress(int $id, array $data): ?Address
    {
        $data['addressable_type'] = $data['addressable_type'] ?? 'user';
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->AddressRepository->update($id, $data);
    }

    public function deleteAddress(int $id): bool
    {
        return $this->AddressRepository->delete($id);
    }
    public function getAddressByLatLong( $lat, $long)
    {
        return $this->zoneRepository->findByLatLong($lat, $long);
    }
}
