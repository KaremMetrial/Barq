<?php

namespace Modules\Compaign\Repositories;
use Modules\Compaign\Models\Compaign;
use Modules\Compaign\Repositories\Contracts\CompaignRepositoryInterface;
use App\Repositories\BaseRepository;
class CompaignRepository extends BaseRepository implements CompaignRepositoryInterface
{
    public function __construct(Compaign $model)
    {
        parent::__construct($model);
    }
}
