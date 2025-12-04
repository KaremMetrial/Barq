<?php

namespace Modules\LoyaltySetting\Repositories;
use Modules\LoyaltySetting\Models\LoyaltySetting;
use Modules\LoyaltySetting\Repositories\Contracts\LoyaltySettingRepositoryInterface;
use App\Repositories\BaseRepository;
class LoyaltySettingRepository extends BaseRepository implements LoyaltySettingRepositoryInterface
{
    public function __construct(LoyaltySetting $model)
    {
        parent::__construct($model);
    }
}
