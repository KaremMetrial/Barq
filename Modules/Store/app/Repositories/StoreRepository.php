<?php

namespace Modules\Store\Repositories;
use Modules\Store\Models\Store;
use Modules\Store\Repositories\Contracts\StoreRepositoryInterface;
use App\Repositories\BaseRepository;
class StoreRepository extends BaseRepository implements StoreRepositoryInterface
{
    public function __construct(Store $model)
    {
        parent::__construct($model);
    }
}
