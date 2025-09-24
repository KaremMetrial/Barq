<?php

namespace Modules\Interest\Repositories;
use Modules\Interest\Models\Interest;
use Modules\Interest\Repositories\Contracts\InterestRepositoryInterface;
use App\Repositories\BaseRepository;
class InterestRepository extends BaseRepository implements InterestRepositoryInterface
{
    public function __construct(Interest $model)
    {
        parent::__construct($model);
    }
}
