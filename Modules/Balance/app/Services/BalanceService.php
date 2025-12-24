<?php

namespace Modules\Balance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Balance\Models\Balance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Balance\Repositories\BalanceRepository;

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
    public function getOrCreateBalance($entity): Balance
    {
        return $entity->balance()->firstOrCreate([], [
            'available_balance' => 0,
            'pending_balance' => 0,
            'total_balance' => 0,
        ]);
    }
    public function addStoreCommission($store, float $amount, string $description = ''): bool
    {
        $balance = $this->getOrCreateBalance($store);

        DB::transaction(function () use ($balance, $amount) {
            $balance->increment('available_balance', $amount);
            $balance->increment('total_balance', $amount);
        });

        // Create transaction record
        $transactionService = app(\App\Services\TransactionService::class);
        $transactionService->createForStore($store, [
            'type' => 'commission',
            'amount' => $amount,
            'currency' => $store->currency_code ?? 'USD',
            'description' => $description ?: "Store commission",
            'status' => 'completed'
        ]);

        return true;
    }
    public function addCourierPayment($courier, float $amount, string $description = ''): bool
    {
        $balance = $this->getOrCreateBalance($courier);

        DB::transaction(function () use ($balance, $amount) {
            $balance->increment('available_balance', $amount);
            $balance->increment('total_balance', $amount);
        });

        // Create transaction record
        $transactionService = app(\App\Services\TransactionService::class);
        $transactionService->createForCourier($courier, [
            'type' => 'delivery_fee',
            'amount' => $amount,
            'currency' => $courier->store->getCurrencyCode() ?? 'EGP',
            'description' => $description ?: "Courier delivery payment",
            'status' => 'completed'
        ]);

        return true;
    }
}
