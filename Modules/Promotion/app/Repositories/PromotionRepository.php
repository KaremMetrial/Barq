<?php

namespace Modules\Promotion\Repositories;
use Modules\Promotion\Models\Promotion;
use Modules\Promotion\Repositories\Contracts\PromotionRepositoryInterface;
use App\Repositories\BaseRepository;
class PromotionRepository extends BaseRepository implements PromotionRepositoryInterface
{
    public function __construct(Promotion $model)
    {
        parent::__construct($model);
    }
}
