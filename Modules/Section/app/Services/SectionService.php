<?php

namespace Modules\Section\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Section\Models\Section;
use Modules\Section\Repositories\SectionRepository;
use Illuminate\Support\Facades\Cache;

class SectionService
{
    public function __construct(
        protected SectionRepository $SectionRepository
    ) {}

    public function getAllSections(): Collection
    {
        return $this->SectionRepository->all();
    }

    public function createSection(array $data): ?Section
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('uploads/icons', 'public');
        }
        return $this->SectionRepository->create($data);
    }

    public function getSectionById(int $id): ?Section
    {
        return $this->SectionRepository->find($id);
    }

    public function updateSection(int $id, array $data): ?Section
    {
        if (request()->hasFile('icon')) {
            $data['icon'] = request()->file('icon')->store('uploads/icons', 'public');
        }
        return $this->SectionRepository->update($id, $data);
    }

    public function deleteSection(int $id): bool
    {
        return $this->SectionRepository->delete($id);
    }
}
