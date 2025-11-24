<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'])
    {
        return $this->model->all($columns);
    }

    public function find(int|string $id, array $relations = [], array $columns = ['*'])
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int|string $id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);

        return $record;
    }

    public function delete(int|string $id): bool
    {
        $record = $this->find($id);
        return $record->delete();
    }

    public function paginate(array $filters = [], int $perPage = 15, array $relations = [], array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model
            ->with($relations)
            ->filter($filters)
            ->latest()
            ->paginate($perPage, $columns);
    }

    public function where(array $conditions, array $columns = ['*'])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get($columns);
    }
    public function firstWhere(array $conditions, array $columns = ['*'])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->first($columns);
    }
    public function updateOrCreate(array $conditions, array $data)
    {
        return $this->model->updateOrCreate($conditions, $data);
    }
    public function allWithTranslations(array $filters = [],array $columns = ['*'])
    {
        return $this->model->filter($filters)->get($columns);
    }
}
