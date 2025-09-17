<?php

namespace Modules\Category\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Category\Models\Category;
use Modules\Category\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $CategoryRepository
    ) {}

    public function getAllCountries(): Collection
    {
        return $this->CategoryRepository->all();
    }

    public function createCategory(array $data): ?Category
    {
        return $this->CategoryRepository->create($data);
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->CategoryRepository->find($id);
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        return $this->CategoryRepository->update($id, $data);
    }

    public function deleteCategory(int $id): bool
    {
        return $this->CategoryRepository->delete($id);
    }
}
