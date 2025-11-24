<?php

namespace Modules\Store\Models;

use App\Models\Report;
use App\Enums\PlanTypeEnum;
use Modules\Cart\Models\Cart;
use Modules\Zone\Models\Zone;
use App\Enums\StoreStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Order\Models\Order;
use Modules\Couier\Models\Couier;
use Modules\Coupon\Models\Coupon;
use Modules\Review\Models\Review;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Modules\Address\Models\Address;
use Modules\Balance\Models\Balance;
use Modules\Product\Models\Product;
use Modules\Section\Models\Section;
use Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Modules\Favourite\Models\Favourite;
use Astrotomic\Translatable\Translatable;
use Modules\WorkingDay\Models\WorkingDay;
use Modules\PosTerminal\Models\PosTerminal;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StoreSetting\Models\StoreSetting;
use Modules\ShippingPrice\Models\ShippingPrice;
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
        'parent_id',
        'branch_type',
        'active_status',
        'commission_type',
        'commission_amount',
        'type'
    ];
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'avg_rate' => 'float',
        'status' => StoreStatusEnum::class,
        'commission_type' => PlanTypeEnum::class,
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
    public function owner()
    {
        return $this->hasOne(Vendor::class)->where('is_owner', true);
    }
    public function couriers(): HasMany
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
        $query;

        if(!empty($filters['type'])) {
            $query->where('type', 'delivery');
        }

        // Set first section if section_id is 0 or empty
        if (empty($filters['section_id']) || $filters['section_id'] == 0) {
            $firstSection = Section::where('type', '!=', 'delivery_company')->latest()->first();

            if ($firstSection) {
                $filters['section_id'] = $firstSection->id;
            }
        }

        if (!empty($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id']) && $filters['category_id'] != 0) {
            $query->whereHas('section.categories', function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['has_offer']) && $filters['has_offer'] == 'true') {
            $query->whereHas('offers', function ($query) {
                $query->where('is_active', true);
            });
        }

        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'delivery_time':
                    $query->join('store_settings', 'stores.id', '=', 'store_settings.store_id')
                        ->orderBy('store_settings.delivery_time', 'asc')
                        ->select('stores.*');
                    break;

                case 'distance':
                    $addressId = request()->header('address-id');

                    if ($addressId) {
                        $address = \DB::table('addresses')->where('id', $addressId)->first();

                        if ($address) {
                            $userLat = $address->latitude;
                            $userLng = $address->longitude;

                            $query->join('addresses', function ($join) {
                                $join->on('stores.id', '=', 'addresses.addressable_id')
                                    ->where('addresses.addressable_type', '=', 'store');
                            })
                                ->selectRaw('stores.*, addresses.id as address_id,
                            (6371 * acos(
                                cos(radians(?)) *
                                cos(radians(addresses.latitude)) *
                                cos(radians(addresses.longitude) - radians(?)) +
                                sin(radians(?)) *
                                sin(radians(addresses.latitude))
                            )) AS distance', [$userLat, $userLng, $userLat])
                                ->orderBy('distance', 'asc');
                        } else {
                            $query->latest();
                        }
                    } else {
                        $query->latest();
                    }
                    break;

                default:
                    $query->latest();
                    break;
            }
        } else {
            if (!empty($filters['rating']) && $filters['rating'] == 'true') {
                $query->orderBy('rating', 'desc');
            } else {
                $query->latest();
            }
        }


        if (auth('sanctum')->check()) {
            if (auth('sanctum')->user()->can('admin')) {
                if (!empty($filters['main']) && $filters['main'] == 'true') {
                    $query->where('parent_id', null);
                }
            }
        } else {
            if (empty($filters['section_id']) || $filters['section_id'] == 0) {
                $firstSection = Section::latest()->first();
                if ($firstSection) {
                    $filters['section_id'] = $firstSection->id;
                }
            }
            $query->where('status', StoreStatusEnum::APPROVED)
            ->where('is_active', true);


        }
        if (!empty($filters['section_id'])) {
            $query->where('section_id', $filters['section_id']);
        }


        $addressId = request()->header('address-id') ?? request()->header('AddressId');
        $lat = request()->header('lat');
        $lng = request()->header('lng');

        $zone = null;

        if ($addressId) {
            $zone = Zone::findZoneByAddressId($addressId);
        } elseif ($lat && $lng) {
            $zone = Zone::findZoneByCoordinates($lat, $lng);
        }

        if ($zone) {
            $query->whereHas('zoneToCover', function ($q) use ($zone) {
                $q->where('zones.id', $zone->id);
            });
        } else {
            if ($addressId || ($lat && $lng)) {
                $query->whereRaw('1 = 0');
            }
        }


        return $query;
    }




    public function getDeliveryFee(?int $vehicleId = null, ?float $distanceKm = null): ?float
    {
        $deliveryFeeService = app(\Modules\Order\Services\DeliveryFeeService::class);
        return $deliveryFeeService->calculateForStore($this, $vehicleId, $distanceKm);
    }

    /**
     * Check if store can deliver to a specific address
     */
    public function canDeliverTo(int $addressId): bool
    {
        $deliveryFeeService = app(\Modules\Order\Services\DeliveryFeeService::class);
        return $deliveryFeeService->canDeliverTo($this, $addressId);
    }

    /**
     * Get delivery zones for this store
     */
    public function getDeliveryZones()
    {
        $storeZoneId = $this->address?->zone_id;
        if (!$storeZoneId) {
            return collect();
        }

        // Get all zones that have shipping prices (delivery areas)
        return \Modules\Zone\Models\Zone::whereHas('shippingPrices')
            ->with(['shippingPrices', 'city'])
            ->get();
    }
    public function currentUserFavourite()
    {
        $token = request()->bearerToken();
        $userId = null;

        if ($token) {
            [, $tokenHash] = explode('|', $token, 2);
            if ($tokenHash) {
                $userId = \DB::table('personal_access_tokens')
                    ->where('token', hash('sha256', $tokenHash))
                    ->value('tokenable_id');
            }
        }

        if (!$userId && auth('user')->check()) {
            $userId = auth('user')->id();
        }

        $relation = $this->morphOne(Favourite::class, 'favouriteable');

        if (!$userId) {
            return $relation->whereRaw('0 = 1');
        }

        return $relation->where('user_id', $userId);
    }

    public function offers()
    {
        return $this->morphMany(Offer::class, 'offerable');
    }
    public function getTaxAmount(): float
    {
        return $this->storeSetting->tax_rate ?? 0.0;
    }
    public function getAddressPlaceAttribute(): ?string
    {
        return $this->address ? $this->address->getFullAddressAttribute() : null;
    }
    public function scopeMainBranches($query)
    {
        return $query->where('branch_type', 'main');
    }
    public function scopeSubBranches($query)
    {
        return $query->where('branch_type', 'branch');
    }
    public function branches(): HasMany
    {
        return $this->hasMany(Store::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'parent_id');
    }
    public function countBranches(): int
    {
        return $this->branches()->count();
    }
    public function countOrders(): int
    {
        return $this->orders()->count();
    }
    // area they serve it
    public function zoneToCover()
    {
       return $this->belongsToMany(Zone::class, 'store_zone', 'store_id', 'zone_id');
    }
}
