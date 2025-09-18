<?php

namespace Modules\Tag\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Tag\Models\Tag;
use Modules\Tag\Repositories\TagRepository;
use Illuminate\Support\Facades\Cache;

class TagService
{
    public function __construct(
        protected TagRepository $TagRepository
    ) {}

    public function getAllTags(): Collection
    {
        return $this->TagRepository->all();
    }

    public function createTag(array $data): ?Tag
    {
        return $this->TagRepository->create($data);
    }

    public function getTagById(int $id): ?Tag
    {
        return $this->TagRepository->find($id);
    }

    public function updateTag(int $id, array $data): ?Tag
    {
        return $this->TagRepository->update($id, $data);
    }

    public function deleteTag(int $id): bool
    {
        return $this->TagRepository->delete($id);
    }
}
