<?php

namespace Modules\Banner\Repositories;
use Modules\Banner\Models\Banner;
use Modules\Banner\Repositories\Contracts\BannerRepositoryInterface;
use App\Repositories\BaseRepository;
class BannerRepository extends BaseRepository implements BannerRepositoryInterface
{
    public function __construct(Banner $model)
    {
        parent::__construct($model);
    }
}
