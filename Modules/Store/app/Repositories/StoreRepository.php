<?php

namespace Modules\Store\Repositories;

use App\Enums\SectionTypeEnum;
use Modules\Store\Models\Store;
use Modules\Section\Models\Section;
use App\Repositories\BaseRepository;
use Modules\Store\Repositories\Contracts\StoreRepositoryInterface;

class StoreRepository extends BaseRepository implements StoreRepositoryInterface
{
    public function __construct(Store $model)
    {
        parent::__construct($model);
    }
    public function getHomeStores(array $relations = [], array $filters = [])
    {
        if (empty($filters['section_id']) || $filters['section_id'] == 0) {
            $firstSection = Section::latest()->first();
            if ($firstSection) {
                $filters['section_id'] = $firstSection->id;
            }
        }

        $featured = $this->model
            ->withTranslation()
            ->with($relations)
            ->filter($filters)
            ->whereIsFeatured(true)
            ->latest()
            ->limit(5)
            ->get();

        $topReviews = $this->model
            ->withTranslation()
            ->with($relations)
            ->filter($filters)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('reviews_avg_rating')
            ->limit(10)
            ->get();

        $newStore = $this->model
            ->withTranslation()
            ->with($relations)
            ->filter($filters)
            ->latest()
            ->limit(5)
            ->get();

        $sectionType = null;
        $sectionLabel = null;

        if (!empty($filters['section_id'])) {
            $section = Section::find($filters['section_id']);
            if ($section && $section->type) {
                $sectionType = $section->type->value;
                $sectionLabel = SectionTypeEnum::label($section->type->value);
            }
        }

        return [
            'topReviews' => $topReviews,
            'featured' => $featured,
            'newStores' => $newStore,
            'section_type' => $sectionType,
            'section_label' => $sectionLabel,
        ];
    }
}
