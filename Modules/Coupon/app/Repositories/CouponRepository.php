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
    public function getAllActive()
    {
        return $this->model->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('is_active', true)
            ->get();
    }
}
