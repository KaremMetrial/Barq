<?php

namespace Modules\Coupon\Repositories;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\BaseRepository;
class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }
}
