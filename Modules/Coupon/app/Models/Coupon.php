<?php

namespace Modules\Coupon\Models;

use App\Enums\SaleTypeEnum;
use App\Models\CouponUsage;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Modules\Store\Models\Store;
use Modules\Reward\Models\Reward;
use Modules\Product\Models\Product;
use Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Coupon extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name'];
    protected $fillable = [
        'code',
        'discount_amount',
        'discount_type',
        'usage_limit',
        'usage_limit_per_user',
        'usage_count',
        'minimum_order_amount',
        'start_date',
        'end_date',
        'is_active',
        'coupon_type',
        'object_type',
        'currency_factor',
        'maximum_order_amount',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'usage_count' => 'integer',
        'discount_type' => SaleTypeEnum::class,
        'coupon_type' => CouponTypeEnum::class,
        'object_type' => ObjectTypeEnum::class,
    ];

    /**
     * Get the currency factor for this couponsa
     *
     * @return int
     */
    public function getCurrencyFactor(): int
    {
        return $this->currency_factor ?? $this->store?->address?->zone?->city?->governorate?->country?->currency_factor ?? 100;
    }

    /**
     * Get the currency symbol for this coupon
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        // Try to get from first associated store
        $store = $this->stores()->first();
        if ($store) {
            return $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP';
        }

        // Fallback to default
        return 'EGP';
    }

    /**
     * Get the discount amount in decimal format (converted from minor units)
     *
     * @return float
     */
    public function getDiscountAmountAttribute($value): float
    {
        $currencyFactor = $this->getCurrencyFactor();
        return \App\Helpers\CurrencyHelper::fromMinorUnits((int) $value, $currencyFactor);
    }

    /**
     * Get the minimum order amount in decimal format (converted from minor units)
     *
     * @return float|null
     */
    public function getMinimumOrderAmountAttribute($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $currencyFactor = $this->getCurrencyFactor();
        return \App\Helpers\CurrencyHelper::fromMinorUnits((int) $value, $currencyFactor);
    }

    /**
     * Get the maximum order amount in decimal format (converted from minor units)
     *
     * @return float|null
     */
    public function getMaximumOrderAmountAttribute($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $currencyFactor = $this->getCurrencyFactor();
        return \App\Helpers\CurrencyHelper::fromMinorUnits((int) $value, $currencyFactor);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_coupon', 'coupon_id', 'category_id');
    }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product', 'coupon_id', 'product_id');
    }
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'coupon_store', 'coupon_id', 'store_id');
    }
        public function isValid(): bool
    {
        return $this->is_active && $this->start_date <= now() && $this->end_date >= now();
    }
    public function getDiscountValue(float $orderAmount): float
    {
        if ($this->discount_type == SaleTypeEnum::PERCENTAGE) {
            return ($orderAmount * $this->discount_amount) / 100;
        }

        return min($orderAmount, $this->discount_amount);
    }
    public function scopeFilter($query, $filters)
    {
        // Handle store filtering for cart-based coupon listing
        if (!empty($filters['store_id'])) {
            $storeId = $filters['store_id'];
            $query->where(function ($q) use ($storeId) {
                // Include general coupons (apply to all stores)
                $q->where('object_type', \App\Enums\ObjectTypeEnum::GENERAL)
                  // Or store-specific coupons that include this store
                  ->orWhere(function ($sq) use ($storeId) {
                      $sq->where('object_type', \App\Enums\ObjectTypeEnum::STORE)
                         ->whereHas('stores', function ($storeQuery) use ($storeId) {
                             $storeQuery->where('stores.id', $storeId);
                         });
                  });
            });

            // Only show active coupons within date range and with remaining usage
            $query->where('is_active', true)
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now())
                  ->whereColumn('usage_count', '<', 'usage_limit');
        } elseif (auth('vendor')->check()) {
            // Original vendor filtering logic
            $vendor = auth('vendor')->user();
            $storeId = $vendor->store_id;

            $query->where(function ($q) use ($storeId) {
                $q->whereHas('stores', function ($qs) use ($storeId) {
                    $qs->where('stores.id', $storeId);
                })
                ->orWhereHas('products', function ($qp) use ($storeId) {
                    $qp->where('products.store_id', $storeId);
                });
            });
            if (!empty($filters['object_type'])) {
                if ($filters['object_type'] == 'product') {
                    $query->whereHas('products', function ($qp) use ($storeId) {
                        $qp->where('products.store_id', $storeId);
                    });
                } elseif ($filters['object_type'] == 'store') {
                    $query->whereHas('stores', function ($qs) use ($storeId) {
                        $qs->where('stores.id', $storeId);
                    });
                }
            }
        }

        // if (!empty($filters['coupon_type'])) {
        //     $query->where('coupon_type', $filters['coupon_type']);
        // }
        // if (!empty($filters['object_type'])) {
        //     $query->where('object_type', $filters['object_type']);
        // }

        if (isset($filters['search']) && $filters['search'] != '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhereHas('translations', function ($qt) use ($search) {
                      $qt->where('name', 'like', "%$search%");
                  });
            });
        }
        if(isset(request()->kart))
        return $query->latest();
    }

    public function usageCount(): int
    {
        return $this->couponUsages()->sum('usage_count') ?? 0;
    }

    public function getUserUsageCount(int $userId): int
    {
        // Using CouponUsage model for tracking per-user usage
        $couponUsage = \App\Models\CouponUsage::where('coupon_id', $this->id)
            ->where('user_id', $userId)
            ->first();

        return $couponUsage ? $couponUsage->usage_count : 0;
    }
   public function rewards()
   {
       return $this->hasMany(Reward::class);
   }
    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

}
