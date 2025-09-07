<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

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

    public function find(int|string $id, array $columns = ['*'])
    {
        return $this->model->findOrFail($id, $columns);
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

    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }

    public function where(array $conditions, array $columns = ['*'])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get($columns);
    }
}
