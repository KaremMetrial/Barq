<?php

namespace Modules\Section\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Section\Models\Section;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Section\Repositories\SectionRepository;

class SectionService
{
    use FileUploadTrait;
    public function __construct(
        protected SectionRepository $SectionRepository
    ) {}

    public function getAllSections(): Collection
    {
        return $this->SectionRepository->all();
    }

    public function createSection(array $data): ?Section
    {
        return DB::transaction(function () use ($data) {
            $data['icon'] = $this->upload(
                request(),
                'icon',
                'uploads/icons',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->SectionRepository->create($data);
        });
    }

    public function getSectionById(int $id): ?Section
    {
        return $this->SectionRepository->find($id);
    }

    public function updateSection(int $id, array $data): ?Section
    {
        return DB::transaction(function () use ($data, $id) {
            $data['icon'] = $this->upload(
                request(),
                'icon',
                'uploads/icons',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->SectionRepository->update($id, $data);
        });

    }

    public function deleteSection(int $id): bool
    {
        return $this->SectionRepository->delete($id);
    }
}
