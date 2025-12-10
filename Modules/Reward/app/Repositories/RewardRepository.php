<?php

namespace Modules\Reward\Repositories;

use Modules\Reward\Models\Reward;
use Modules\Reward\Models\RewardRedemption;
use Modules\Reward\Repositories\Contracts\RewardRepositoryInterface;

class RewardRepository implements RewardRepositoryInterface
{
    public function all()
    {
        return Reward::all();
    }

    public function paginate(array $filters = [], int $perPage = 15, array $relations = [])
    {
        return Reward::filter($filters)
            ->with($relations)
            ->paginate($perPage);
    }

    public function find(int $id, array $relations = [])
    {
        return Reward::with($relations)->findOrFail($id);
    }

    public function create(array $data)
    {
        return Reward::create($data);
    }

    public function update(int $id, array $data)
    {
        $reward = $this->find($id);
        $reward->update($data);
        return $reward->fresh();
    }

    public function delete(int $id): bool
    {
        $reward = $this->find($id);
        return $reward->delete();
    }

    public function getAvailableRewards(array $filters = [], array $relations = [])
    {
        $query = Reward::active()->available();

        if (isset($filters['country_id'])) {
            $query->forCountry($filters['country_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['max_points'])) {
            $query->where('points_cost', '<=', $filters['max_points']);
        }

        if (isset($filters['is_it_for_loyalty_points'])) {
            $query->where('is_it_for_loyalty_points', $filters['is_it_for_loyalty_points']);
        }

        if (isset($filters['is_it_for_spendings'])) {
            $query->where('is_it_for_spendings', $filters['is_it_for_spendings']);
        }

        return $query->with($relations)->get();
    }

    public function getUserRedemptions(int $userId, array $relations = [])
    {
        return RewardRedemption::where('user_id', $userId)
            ->with($relations)
            ->latest()
            ->paginate(15);
    }

    public function createRedemption(array $data)
    {
        return RewardRedemption::create($data);
    }

    public function incrementTotalRedemptions(int $rewardId)
    {
        $reward = $this->find($rewardId);
        $reward->increment('total_redemptions');
        return $reward;
    }
}
