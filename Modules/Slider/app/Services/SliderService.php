<?php

namespace Modules\Slider\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Slider\Models\Slider;
use Modules\Slider\Repositories\SliderRepository;
use Illuminate\Support\Facades\Cache;

class SliderService
{
    use FileUploadTrait;
    public function __construct(
        protected SliderRepository $SliderRepository
    ) {}

    public function getAllSliders($filters = [])
    {
        return $this->SliderRepository->allWithTranslations($filters);
    }

    public function createSlider(array $data): ?Slider
    {
        if (request()->hasFile('image')) {
            $data['image'] = $this->upload(request(), 'image', 'uploads/sliders', 'public',$data['resize'] ?? [393,152]);
        }
        return $this->SliderRepository->create($data);
    }

    public function getSliderById(int $id): ?Slider
    {
        return $this->SliderRepository->find($id, []);
    }

    public function updateSlider(int $id, array $data): ?Slider
    {
        if (request()->hasFile('image')) {
            $data['image'] = $this->upload(request(), 'image', 'uploads/sliders', 'public',$data['resize'] ?? [393,152]);
        }
        return $this->SliderRepository->update($id, $data);
    }

    public function deleteSlider(int $id): bool
    {
        return $this->SliderRepository->delete($id);
    }

    public function getIndex()
    {
        return $this->SliderRepository->allWithTranslations([]);
    }
}
