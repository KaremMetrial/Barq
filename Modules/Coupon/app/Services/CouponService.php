<?php

namespace Modules\Coupon\Services;

use Illuminate\Support\Facades\DB;
use Modules\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Coupon\Repositories\CouponRepository;

class CouponService
{
    public function __construct(
        protected CouponRepository $CouponRepository
    ) {}

    public function getAllCoupons($filters = [])
    {
        return $this->CouponRepository->paginate($filters);
    }

    public function createCoupon(array $data): ?Coupon
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            $coupon = $this->CouponRepository->create($data);
            if (isset($data['category_ids'])) {
                $coupon->categories()->sync($data['category_ids']);
            }
            if (isset($data['product_ids'])) {
                $coupon->products()->sync($data['product_ids']);
            }
            if (isset($data['store_ids'])) {
                $coupon->stores()->sync($data['store_ids']);
            }
            return $coupon;
        });
    }

    public function getCouponById(int $id): ?Coupon
    {
        return $this->CouponRepository->find($id);
    }

    public function updateCoupon(int $id, array $data): ?Coupon
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            $coupon = $this->CouponRepository->update($id, $data);
            if (isset($data['category_ids'])) {
                $coupon->categories()->sync($data['category_ids']);
            }
            if (isset($data['product_ids'])) {
                $coupon->products()->sync($data['product_ids']);
            }
            if (isset($data['store_ids'])) {
                $coupon->stores()->sync($data['store_ids']);
            }
            return $coupon;
        });
    }
    public function deleteCoupon(int $id): bool
    {
        return $this->CouponRepository->delete($id);
    }
}
