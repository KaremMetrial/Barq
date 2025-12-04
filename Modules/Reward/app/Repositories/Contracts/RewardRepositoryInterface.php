<?php

namespace Modules\Reward\Repositories\Contracts;

interface RewardRepositoryInterface
{
    public function all();
    public function paginate(array $filters = [], int $perPage = 15, array $relations = []);
    public function find(int $id, array $relations = []);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
    public function getAvailableRewards(array $filters = [], array $relations = []);
    public function getUserRedemptions(int $userId, array $relations = []);
}
