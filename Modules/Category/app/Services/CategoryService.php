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

    public function getAllCountries($filters = []): Collection
    {
        return $this->CategoryRepository->allWithTranslations($filters);
    }

    public function createCategory(array $data): ?Category
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('uploads/icons', 'public');
        }
        return $this->CategoryRepository->create($data);
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->CategoryRepository->find($id, ['children']);
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('uploads/icons', 'public');
        }
        return $this->CategoryRepository->update($id, $data);
    }

    public function deleteCategory(int $id): bool
    {
        return $this->CategoryRepository->delete($id);
    }
}
