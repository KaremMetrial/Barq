<?php

namespace Modules\Page\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Page\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Page\Repositories\PageRepository;

class PageService
{
    use FileUploadTrait;
    public function __construct(
        protected PageRepository $PageRepository
    ) {}

    public function getAllPages(): Collection
    {
        return $this->PageRepository->all();
    }

    public function createPage(array $data): ?Page
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->PageRepository->create($data);
        });
    }

    public function getPageById(int $id): ?Page
    {
        return $this->PageRepository->find($id, ['vendor', 'posTerminal']);
    }

    public function updatePage(int $id, array $data): ?Page
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->PageRepository->update($id, $data);
        });
    }

    public function deletePage(int $id): bool
    {
        return $this->PageRepository->delete($id);
    }
}
