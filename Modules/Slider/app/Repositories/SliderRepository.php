<?php

namespace Modules\Slider\Repositories;
use Modules\Slider\Models\Slider;
use Modules\Slider\Repositories\Contracts\SliderRepositoryInterface;
use App\Repositories\BaseRepository;
class SliderRepository extends BaseRepository implements SliderRepositoryInterface
{
    public function __construct(Slider $model)
    {
        parent::__construct($model);
    }
}
