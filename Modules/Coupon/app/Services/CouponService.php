<?php

namespace Modules\Coupon\Services;

use Illuminate\Support\Facades\DB;
use Modules\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Coupon\Repositories\CouponRepository;
use Modules\Country\Models\Country;
use Modules\Reward\Models\Reward;
use Modules\Cart\Models\Cart;

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
            // Convert discount amount based on currency factor before saving
            $currencyFactor = $this->getCurrentCountryCurrencyFactor($data);
            if (isset($data['discount_amount'])) {
                $data['discount_amount'] = $this->convertToMinorUnit($data['discount_amount'], $currencyFactor);
            }
            
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
            // Convert discount amount based on currency factor before saving
            $currencyFactor = $this->getCurrentCountryCurrencyFactor($data);
            if (isset($data['discount_amount'])) {
                $data['discount_amount'] = $this->convertToMinorUnit($data['discount_amount'], $currencyFactor);
            }
            
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
    
    /**
     * Get coupons for authenticated user based on their rewards and cart
     *
     * @param array $filters
     * @return mixed
     */
    public function getUserCoupons(array $filters = [])
    {
        $query = Coupon::query();
        
        // Filter by user's rewards
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            
            // Get coupons from user's rewards
            $rewardCoupons = Reward::where('user_id', $user->id)
                ->whereNotNull('coupon_id')
                ->pluck('coupon_id');
                
            if ($rewardCoupons->isNotEmpty()) {
                $query->whereIn('id', $rewardCoupons);
            } else {
                // If no reward coupons, return empty collection
                return collect();
            }
        }
        
        // Filter by cart store if cart key provided
        if (request()->header('Cart-Key') || request()->input('cart_key')) {
            $cartKey = request()->header('Cart-Key') ?? request()->input('cart_key');
            $cart = Cart::where('cart_key', $cartKey)->first();
            
            if ($cart && $cart->items->isNotEmpty()) {
                // Get store IDs from cart items
                $storeIds = $cart->items->pluck('product.store_id')->unique()->filter();
                
                if ($storeIds->isNotEmpty()) {
                    $query->whereHas('stores', function ($q) use ($storeIds) {
                        $q->whereIn('stores.id', $storeIds);
                    })->orWhereDoesntHave('stores'); // Also include general coupons
                }
            }
        }
        
        // Apply other filters
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        return $query->get();
    }
    
    /**
     * Calculate discount value considering currency factor
     *
     * @param Coupon $coupon
     * @param float $orderAmount
     * @return float
     */
    public function calculateDiscount(Coupon $coupon, float $orderAmount): float
    {
        // Get the currency factor (default to 100 if not set)
        $currencyFactor = $coupon->getCurrencyFactor();
        
        if ($coupon->discount_type == \App\Enums\SaleTypeEnum::PERCENTAGE) {
            return ($orderAmount * $coupon->discount_amount) / 100;
        }
        
        // For fixed amount discounts, adjust by currency factor
        // Convert from smallest currency unit to main unit
        $adjustedDiscount = $coupon->discount_amount / $currencyFactor;
        return min($orderAmount, $adjustedDiscount);
    }
    
    /**
     * Format amount according to coupon's currency factor
     *
     * @param Coupon $coupon
     * @param float $amount
     * @return float
     */
    public function formatAmount(Coupon $coupon, float $amount): float
    {
        $currencyFactor = $coupon->getCurrencyFactor();
        return $amount * $currencyFactor;
    }
    
    /**
     * Convert amount from major unit to minor unit based on currency factor
     *
     * @param float $amount
     * @param int $currencyFactor
     * @return int
     */
    public function convertToMinorUnit(float $amount, int $currencyFactor): int
    {
        return intval(round($amount * $currencyFactor));
    }
    
    /**
     * Convert amount from minor unit to major unit based on currency factor
     *
     * @param int $amount
     * @param int $currencyFactor
     * @return float
     */
    public function convertToMajorUnit(int $amount, int $currencyFactor): float
    {
        return $amount / $currencyFactor;
    }
    
    /**
     * Get currency factor from current country or provided data
     *
     * @param array $data
     * @return int
     */
    private function getCurrentCountryCurrencyFactor(array $data): int
    {
        // If currency_factor is explicitly provided in data, use it
        if (isset($data['currency_factor']) && is_numeric($data['currency_factor'])) {
            return (int) $data['currency_factor'];
        }
        
        // Try to get currency factor from authenticated user's country
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            return $user->getCountryCurrencyFactor();
        }
        
        // Try to get country_id from data and fetch its currency factor
        if (isset($data['country_id']) && is_numeric($data['country_id'])) {
            $country = Country::find($data['country_id']);
            if ($country) {
                return $country->currency_factor ?? 100;
            }
        }
        
        // Default to 100 if no country found
        return 100;
    }
}