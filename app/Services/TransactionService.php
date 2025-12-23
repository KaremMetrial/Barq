<?php

namespace App\Services;

use App\Models\Transaction;
use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\Couier\Models\Couier;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create a transaction for a user
     */
    public function createForUser(User $user, array $data): Transaction
    {
        return $this->createTransaction($user, $data);
    }

    /**
     * Create a transaction for a store
     */
    public function createForStore(Store $store, array $data): Transaction
    {
        return $this->createTransaction($store, $data);
    }

    /**
     * Create a transaction for a courier
     */
    public function createForCourier(Couier $courier, array $data): Transaction
    {
        return $this->createTransaction($courier, $data);
    }

    /**
     * Generic method to create a transaction for any entity
     */
    protected function createTransaction($entity, array $data): Transaction
    {
        $transactionData = array_merge($data, [
            'transactionable_type' => get_class($entity),
            'transactionable_id' => $entity->id,
        ]);

        // For backward compatibility, also set user_id if the entity is a user
        if ($entity instanceof User) {
            $transactionData['user_id'] = $entity->id;
        }

        return Transaction::create($transactionData);
    }

    /**
     * Get transactions for a specific entity
     */
    public function getForEntity($entity): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('transactionable_type', get_class($entity))
                         ->where('transactionable_id', $entity->id)
                         ->orderBy('created_at', 'desc')
                         ->get();
    }

    /**
     * Get transactions for a user (maintaining backward compatibility)
     */
    public function getForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere(function($q) use ($user) {
                      $q->where('transactionable_type', get_class($user))
                        ->where('transactionable_id', $user->id);
                  });
        })
        ->orderBy('created_at', 'desc')
        ->get();
    }
}
