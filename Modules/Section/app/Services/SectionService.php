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

    public function getAllSections($filters = []): Collection
    {
        return $this->SectionRepository->allWithTranslations($filters);
    }

    public function createSection(array $data): ?Section
    {
        return DB::transaction(function () use ($data) {
            if ($icon = $this->uploadIcon(request(), 'icon')) {
                $data['icon'] = $icon;
            }
            $data = array_filter($data, fn($value) => !blank($value));
            $section = $this->SectionRepository->create($data);
            $this->syncCategories($section, $data);
            $this->syncCountries($section, $data);
            return $section->refresh();
        });
    }

    public function syncCountries(Section $section, array $data): void
    {
        if (isset($data['countries'])) {
            $section->country()->sync($data['countries']);
        }
    }

    public function getSectionById(int $id): ?Section
    {
        return $this->SectionRepository->find($id,['categories']);
    }

    public function updateSection(int $id, array $data): ?Section
    {
        return DB::transaction(function () use ($data, $id) {
            if ($icon = $this->uploadIcon(request(), 'icon')) {
                $data['icon'] = $icon;
            }
            $data = $this->transformTranslatableFields($data);
            $data = array_filter($data, fn($value) => !blank($value));
            $section = $this->SectionRepository->find($id);
            $section->update($data);
            $this->syncCategories($section, $data);
            $this->syncCountries($section, $data);
            return $section->refresh();
        });
    }

    public function deleteSection(int $id): bool
    {
        return $this->SectionRepository->delete($id);
    }
    protected function transformTranslatableFields(array $data): array
    {
        $fields = ['name'];
        foreach ($fields as $field) {
            if (isset($data[$field], $data['lang'])) {
                $data["{$field}:{$data['lang']}"] = $data[$field];
                unset($data[$field]);
            }
        }
        unset($data['lang']);
        return $data;
    }
    private function uploadIcon($request, string $fileKey): ?string
    {

        if ($request->hasFile($fileKey) && $request->file($fileKey)->isValid()) {
            return $this->upload(
                $request,
                $fileKey,
                'uploads/icons',
                'public'
            );
        }
        return null;
    }
    private function syncCategories(Section $section, array $data): void
    {
        if (isset($data['categories'])) {
            $section->categories()->sync($data['categories']);
        }
    }
}
