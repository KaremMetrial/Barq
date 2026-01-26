<?php

namespace Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use App\Enums\PromotionTypeEnum;
use App\Enums\PromotionSubTypeEnum;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

class Promotion extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['title', 'description'];

    protected $fillable = [
        'type',
        'sub_type',
        'is_active',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_limit_per_user',
        'current_usage',
        'country_id',
        'city_id',
        'zone_id',
        'min_order_amount',
        'max_order_amount',
        'discount_value',
        'fixed_delivery_price',
        'currency_factor',
        'first_order_only',
    ];
    protected $with = ['translations'];
    protected $casts = [
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'current_usage' => 'integer',
        'is_active' => 'boolean',
        'first_order_only' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'type' => PromotionTypeEnum::class,
        'sub_type' => PromotionSubTypeEnum::class,
    ];
    public function targets()
    {
        return $this->hasMany(PromotionTarget::class, 'promotion_id');
    }
    
    public function promotionTargets()
    {
        return $this->hasMany(PromotionTarget::class, 'promotion_id');
    }
    
    public function fixedPrices()
    {
        return $this->hasMany(PromotionFixedPrice::class, 'promotion_id');
    }
    
    public function promotionFixedPrices()
    {
        return $this->hasMany(PromotionFixedPrice::class, 'promotion_id');
    }
    public function userPromotionUsage()
    {
        return $this->hasMany(UserPromotionUsage::class, 'promotion_id');
    }

    // Alias for service layer compatibility
    public function userUsages()
    {
        return $this->userPromotionUsage();
    }

    public function isValidForDelivery(Store $store, float $orderAmount, User $user = null): bool
    {
        if (!$this->isValid()) return false;

        if ($this->type != PromotionTypeEnum::DELIVERY) return false;

        if ($this->country_id && $store->country_id !== $this->country_id) return false;
        if ($this->city_id && $store->city_id !== $this->city_id) return false;
        if ($this->zone_id && $store->zone_id !== $this->zone_id) return false;

        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) return false;
        if ($this->max_order_amount && $orderAmount > $this->max_order_amount) return false;

        if ($this->first_order_only && $user && !$this->isUserFirstOrder($user)) return false;

        return true;
    }
    public function isValid(): bool
    {
        return $this->is_active && $this->start_date <= now() && ($this->end_date === null || $this->end_date >= now());
    }
    public function isUserFirstOrder(User $user): bool
    {
        return $user->orders()->count() == 0;
    }
    public function calculateDeliveryCost(Store $store, float $baseDeliveryCost, float $orderAmount, User $user = null): float
    {
        if (!$this->isValidForDelivery($store, $orderAmount, $user)) {
            return $baseDeliveryCost;
        }

        switch ($this->sub_type) {
            case 'FREE_DELIVERY':
                return 0;
            case 'DISCOUNT_DELIVERY':
                return max(0, $baseDeliveryCost - ($this->discount_value ?? 0));
            case 'FIXED_DELIVERY':
                return $this->fixed_delivery_price ?? 0;
            default:
                return $baseDeliveryCost;
        }
    }
}
