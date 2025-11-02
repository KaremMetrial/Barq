<?php

namespace Modules\Address\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\AddressRepository;
use Illuminate\Support\Facades\Cache;

class AddressService
{
    public function __construct(
        protected AddressRepository $AddressRepository
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
        return $this->AddressRepository->find($id);
    }

    public function updateAddress(int $id, array $data): ?Address
    {
        return $this->AddressRepository->update($id, $data);
    }

    public function deleteAddress(int $id): bool
    {
        return $this->AddressRepository->delete($id);
    }
}
