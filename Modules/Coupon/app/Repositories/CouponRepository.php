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

        $query = $this->model->with($relations)->filter($filters);

        // Include user's reward coupon codes
        if (!empty($filters['user_coupon_codes'])) {
            $rewardCoupons = $this->model->whereIn('code', $filters['user_coupon_codes'])
                ->with($relations)
                ->get();

            // Merge with regular coupons
            $regularCoupons = $query->get();
            $allCoupons = $regularCoupons->merge($rewardCoupons);

            // Paginate the merged collection
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $items = $allCoupons->slice(($currentPage - 1) * $perPage, $perPage)->values();
            
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $allCoupons->count(),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );
        }

        return $query->paginate($perPage, $columns);
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
