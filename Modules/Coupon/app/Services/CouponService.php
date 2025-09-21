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

    public function getAllCoupons(): Collection
    {
        return $this->CouponRepository->all();
    }

    public function createCoupon(array $data): ?Coupon
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->CouponRepository->create($data);
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
            return $this->CouponRepository->update($id, $data);
        });
    }

    public function deleteCoupon(int $id): bool
    {
        return $this->CouponRepository->delete($id);
    }
}
