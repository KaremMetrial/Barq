<?php

namespace Modules\Coupon\Repositories;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\BaseRepository;
use App\Enums\ObjectTypeEnum;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15, array $relations = [], array $columns = ['*']): \Illuminate\Pagination\LengthAwarePaginator
    {
        $relations = array_merge($relations, ['stores.address.zone.city.governorate.country']);

        return $this->model
            ->with($relations)
            ->filter($filters)
            ->latest()
            ->paginate($perPage, $columns);
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
