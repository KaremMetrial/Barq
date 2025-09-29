<?php

namespace App\Repositories\Contracts;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']);

    public function find(int|string $id, array $relations = [], array $columns = ['*']);

    public function create(array $data);

    public function update(int|string $id, array $data);

    public function delete(int|string $id): bool;

    public function paginate(int $perPage = 15, array $relations = [], array $columns = ['*']);

    public function where(array $conditions, array $columns = ['*']);
    public function firstWhere(array $conditions, array $columns = ['*']);
    public function updateOrCreate(array $conditions, array $data);
}
