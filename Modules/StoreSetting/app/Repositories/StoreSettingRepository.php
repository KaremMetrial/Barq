<?php

namespace Modules\StoreSetting\Repositories;
use Modules\StoreSetting\Models\StoreSetting;
use Modules\StoreSetting\Repositories\Contracts\StoreSettingRepositoryInterface;
use App\Repositories\BaseRepository;
class StoreSettingRepository extends BaseRepository implements StoreSettingRepositoryInterface
{
    public function __construct(StoreSetting $model)
    {
        parent::__construct($model);
    }
}
