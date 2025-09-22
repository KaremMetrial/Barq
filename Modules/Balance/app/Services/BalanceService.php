<?php

namespace Modules\Balance\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Balance\Models\Balance;
use Modules\Balance\Repositories\BalanceRepository;
use Illuminate\Support\Facades\Cache;

class BalanceService
{
    public function __construct(
        protected BalanceRepository $BalanceRepository
    ) {}

    public function getAllBalances(): Collection
    {
        return $this->BalanceRepository->all();
    }

    public function createBalance(array $data): ?Balance
    {
        return $this->BalanceRepository->create($data)->refresh();
    }

    public function getBalanceById(int $id): ?Balance
    {
        return $this->BalanceRepository->find($id);
    }

    public function updateBalance(int $id, array $data): ?Balance
    {
        return $this->BalanceRepository->update($id, $data);
    }

    public function deleteBalance(int $id): bool
    {
        return $this->BalanceRepository->delete($id);
    }
}
