<?php

namespace Modules\Coupon\Services;

use Illuminate\Support\Facades\DB;
use Modules\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Coupon\Repositories\CouponRepository;
use App\Helpers\CurrencyHelper;

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

            // Convert monetary amounts to minor units if provided
            $currencyFactor = $data['currency_factor'] ?? null;
            if ($currencyFactor) {
                if (isset($data['discount_amount'])) {
                    $data['discount_amount'] = CurrencyHelper::toMinorUnits( $data['discount_amount'], $currencyFactor);
                }
                if (isset($data['minimum_order_amount'])) {
                    $data['minimum_order_amount'] = CurrencyHelper::toMinorUnits( $data['minimum_order_amount'], $currencyFactor);
                }
                if (isset($data['maximum_order_amount'])) {
                    $data['maximum_order_amount'] = CurrencyHelper::toMinorUnits( $data['maximum_order_amount'], $currencyFactor);
                }
            }

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

            // Convert monetary amounts to minor units if provided
            $currencyFactor = $data['currency_factor'] ?? null;
            if ($currencyFactor) {
                if (isset($data['discount_amount'])) {
                    $data['discount_amount'] = CurrencyHelper::toMinorUnits( $data['discount_amount'], (int) $currencyFactor);
                }
                if (isset($data['minimum_order_amount'])) {
                    $data['minimum_order_amount'] = CurrencyHelper::toMinorUnits( $data['minimum_order_amount'], (int) $currencyFactor);
                }
                if (isset($data['maximum_order_amount'])) {
                    $data['maximum_order_amount'] = CurrencyHelper::toMinorUnits( $data['maximum_order_amount'], (int) $currencyFactor);
                }
            }

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
