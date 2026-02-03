<?php

namespace Modules\Category\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Category\Models\Category;
use Modules\Category\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    use FileUploadTrait;
    public function __construct(
        protected CategoryRepository $CategoryRepository
    ) {}

    public function getAllCategories($filters = []): Collection
    {
        return $this->CategoryRepository->allWithTranslations($filters);
    }

    public function createCategory(array $data): ?Category
    {
        return DB::transaction(function () use ($data) {
            if (request()->hasFile('icon')) {
                $data['icon'] = $this->upload(request(), 'icon', 'uploads/icons', 'public', [512,512]);
            }
            $data = array_filter($data, fn($value) => !blank($value));
            $category = $this->CategoryRepository->create($data);
            if($data['section_ids']){
                $category->sections()->sync($data['section_ids']);
            }
            return $category;
        });
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->CategoryRepository->find($id, ['children']);
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        return DB::transaction(function () use ($id, $data) {
            if (request()->hasFile('icon')) {
                $data['icon'] = $this->upload(request(), 'icon', 'uploads/icons', 'public', [512,512]);
            }
            $data = array_filter($data, fn($value) => !blank($value));
            $category = $this->CategoryRepository->update($id, $data);
            if($data['section_ids']){
                $category->sections()->sync($data['section_ids']);
            }
            return $category;
        });
    }

    public function deleteCategory(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->CategoryRepository->delete($id);
        });
    }
}
