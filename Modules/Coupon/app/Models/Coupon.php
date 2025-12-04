<?php

namespace Modules\Coupon\Models;

use App\Enums\SaleTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Modules\Store\Models\Store;
use Modules\Product\Models\Product;
use Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Modules\Reward\Models\Reward;
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
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_amount' => 'decimal:3',
        'minimum_order_amount' => 'decimal:3',
        'usage_count' => 'integer',
        'discount_type' => SaleTypeEnum::class,
        'coupon_type' => CouponTypeEnum::class,
        'object_type' => ObjectTypeEnum::class,
    ];
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
        if (auth('vendor')->check()) {
            $query->whereHas('stores', function ($q) {
                $q->where('stores.id', auth('vendor')->user()->store_id);
            });
        }
        if (isset($filters['search']) && $filters['search'] != '')
        {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhereHas('translations', function ($qt) use ($search) {
                      $qt->where('name', 'like', "%$search%");
                  });
            });
        }
        return $query->latest();
    }

    public function usageCount(): int
    {
        return $this->usage_count ?? 0;
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
}
