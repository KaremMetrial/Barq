<?php

namespace Modules\Store\Models;

use App\Models\Review;
use App\Models\ShippingPrice;
use Modules\Cart\Models\Cart;
use App\Enums\StoreStatusEnum;
use Modules\Coupon\Models\Coupon;
use Modules\Vendor\Models\Vendor;
use Modules\Address\Models\Address;
use Modules\Balance\Models\Balance;
use Modules\Product\Models\Product;
use Modules\Section\Models\Section;
use Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Modules\Favourite\Models\Favourite;
use Astrotomic\Translatable\Translatable;
use Modules\WorkingDay\Models\WorkingDay;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StoreSetting\Models\StoreSetting;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\CompaignParicipation\Models\CompaignParicipation;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Store extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;
    public $translatedAttributes = ['name'];

    protected $fillable = [
        'status',
        'note',
        'message',
        'logo',
        'cover_image',
        'phone',
        'is_featured',
        'is_active',
        'is_closed',
        'avg_rate',
        'section_id',
    ];
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'avg_rate' => 'float',
        'status' => StoreStatusEnum::class,
    ];
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
    public function workingDays(): HasMany
    {
        return $this->hasMany(WorkingDay::class);
    }
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_store', 'store_id', 'coupon_id');
    }
    public function CompaignParicipations(): HasMany
    {
        return $this->hasMany(CompaignParicipation::class, 'store_id');
    }
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
    public function couiers(): HasMany
    {
        return $this->hasMany(Couier::class);
    }
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }
    public function favourites(): MorphMany
    {
        return $this->morphMany(Favourite::class, 'favouriteable');
    }
    public function storeSetting(): HasOne
    {
        return $this->hasOne(StoreSetting::class, 'store_id');
    }
    public function posTerminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class);
    }
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function balance(): MorphOne
    {
        return $this->morphOne(Balance::class, 'balanceable');
    }
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    public function scopeFilter($query, $filters)
    {
        return $query->whereStatus(StoreStatusEnum::APPROVED)->whereIsActive(true);
    }
    public function getDeliveryFee(?int $vehicleId = null, ?float $distanceKm = null): ?float
    {
        $zoneId = $this->address?->zone_id;
        if (!$zoneId) {
            return null;
        }

        $shippingPriceQuery = ShippingPrice::where('zone_id', $zoneId);
        if ($vehicleId) {
            $shippingPriceQuery->where('vehicle_id', $vehicleId);
        }
        $shippingPrice = $shippingPriceQuery->first();

        if (!$shippingPrice) {
            return null;
        }
        $distanceKm = $distanceKm ?? 0;

        $fee = $shippingPrice->base_price + ($shippingPrice->per_km_price * $distanceKm);

        if ($shippingPrice->max_price && $fee > $shippingPrice->max_price) {
            $fee = $shippingPrice->max_price;
        }

        return round($fee, 2);
    }
}
