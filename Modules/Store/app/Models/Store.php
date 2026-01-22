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
use Illuminate\Support\Facades\DB;
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
use Modules\AddOn\Models\AddOn;
use Modules\Banner\Models\Banner;
use Laravel\Scout\Searchable;

class Store extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable, Searchable;
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
        'type',
        'currency_code',
        'currency_symbol',
        'currency_factor',
        'iban'
    ];
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'avg_rate' => 'float',
        'status' => StoreStatusEnum::class,
        'commission_type' => PlanTypeEnum::class,
    ];
    protected $with = ['workingDays', 'address', 'reviews', 'translations','categories'];
    public function toSearchableArray()
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
        ];
    }
    public function getTotalEarnedCommission(): float
    {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->get()
            ->sum(fn($order) => $this->calculateCommission($order->total_amount));
    }

    /**
     * Get total pending commission for this store
     */
    public function getTotalPendingCommission(): float
    {
        return $this->orders()
            ->where('payment_status', '!=', 'paid')
            ->get()
            ->sum(fn($order) => $this->calculateCommission($order->total_amount));
    }
    public function scopeWithCustomCommission($query)
    {
        return $query->where(function ($query) {
            $query->where('commission_amount', '>', 0)
                ->orWhere('commission_type', '!=', PlanTypeEnum::COMMISSION);
        });
    }

    /**
     * Calculate commission for a specific order amount
     */
    public function calculateCommission($orderAmount)
    {
        if ($this->commission_type === PlanTypeEnum::COMMISSION) {
            // commission_amount is stored as a percentage (e.g., 10 for 10%)
            return ($orderAmount * $this->commission_amount) / 100;
        } elseif ($this->commission_type === PlanTypeEnum::SUBSCRIPTION) {
            // commission_amount is stored as minor units (fixed amount)
            return $this->commission_amount;
        }
        return 0;
    }

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
    public function addOns(): HasMany
    {
        return $this->hasMany(AddOn::class);
    }
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
    public function banners()
    {
        return $this->morphMany(Banner::class, 'bannerable');
    }

    public function scopeFilter($query, $filters)
    {
        $query;
        if (!empty($filters['country_id'])) {
            $query->whereHas('address', function ($query) use ($filters) {
                $query->where('country_id', $filters['country_id']);
            });
        }
        if (!empty($filters['city_id'])) {
            $query->whereHas('address', function ($query) use ($filters) {
                $query->where('city_id', $filters['city_id']);
            });
        }
        if (!empty($filters['zone_id'])) {
            $query->whereHas('address', function ($query) use ($filters) {
                $query->where('zone_id', $filters['zone_id']);
            });
        }
        if (!empty($filters['type'])) {
            $query->where('type', 'delivery');
        } else {
            $query->where('type', 'store');
        }

        if (!empty($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id']) && $filters['category_id'] != 0) {
            $query->whereHas('products.category', function ($query) use ($filters) {
                $query->where('id', $filters['category_id']);
            });
        }

        if (!empty($filters['has_offer']) && ($filters['has_offer'] == 'true' || $filters['has_offer'] == 1 || $filters['has_offer'] === true)) {
            $query->whereHas('products', function ($query) {
                $query->whereHas('offers', function ($query) {
                    $query->where('is_active', true)
                        ->whereDate('start_date', '<=', now()->toDateString())
                        ->whereDate('end_date', '>=', now()->toDateString());
                });
            });
        }

        if (!empty($filters['is_active']) && $filters['is_active'] == 'true') {
            $query->where('is_active', true);
        }

        if (!empty($filters['is_active']) && $filters['is_active'] == 'false') {
            $query->where('is_active', false);
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
            $user = auth('sanctum')->user();
            if ($user && ($user->tokenCan('admin') || $user->tokenCan('vendor'))) {
                if (!empty($filters['main']) && $filters['main'] == 'true') {
                    $query->where('parent_id', null);
                }
            } else {
                if (empty($filters['section_id']) || $filters['section_id'] == 0) {
                    $firstSection = Section::where('type', '!=', 'delivery_company')->latest()->first();
                    if ($firstSection) {
                        $filters['section_id'] = $firstSection->id;
                    }
                }
                $query->where('status', StoreStatusEnum::APPROVED)
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->whereHas('products', function ($query) {
                        $query->where('is_active', true);
                    })
                    ->where('type', '!=', 'delivery');
            }
        } else {
            // Unauthenticated users get default section and filters
            if (empty($filters['section_id']) || $filters['section_id'] == 0) {
                $firstSection = Section::where('type', '!=', 'delivery_company')->latest()->first();
                if ($firstSection) {
                    $filters['section_id'] = $firstSection->id;
                }
            }
            $query->where('status', StoreStatusEnum::APPROVED)
                ->whereNull('parent_id')
                ->whereHas('products', function ($query) {
                    $query->where('is_active', true);
                })
                ->where('is_active', true)
                ->where('type', '!=', 'delivery');
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
            $query->where(function ($query) use ($zone) {
                $query->whereHas('zoneToCover', function ($q) use ($zone) {
                    $q->where('zones.id', $zone->id);
                })
                    ->orWhereHas('branches', function ($q) use ($zone) {
                        $q->whereHas('zoneToCover', function ($qq) use ($zone) {
                            $qq->where('zones.id', $zone->id);
                        });
                    });
            });
        } else {
            if ($addressId || ($lat && $lng)) {
                $query->whereRaw('1 = 0');
            }
        }
        return $query;
    }
    public function getProductCategories()
    {
        // If categories are already loaded, we can filter them in memory
        // if ($this->relationLoaded('categories')) {
        //     $categories = $this->categories;
        //     if ($this->section->type->value != 'restaurant') {
        //         return $categories->where('parent_id', null);
        //     }
        //     return $categories;
        // }

        // Fallback to existing logic if not loaded
        if ($this->section->type->value != 'restaurant') {
            return Category::with('children')
                ->whereNull('parent_id')
                ->whereHas('products', function ($query) {
                    $query->where('store_id', $this->id);
                })
                ->orWhere(function ($query) {
                    $query->whereHas('children', function ($q) {
                        $q->whereHas('products', function ($qq) {
                            $qq->where('store_id', $this->id);
                        });
                    });
                })
                ->get();
        }

        return Category::with('children')
            ->whereHas('products', function ($query) {
                $query->where('store_id', $this->id);
            })
            ->get();
    }



    public function getReviewCountFormatted(): string
    {
        $reviewCount = $this->reviews_count ?? $this->orders()
            ->whereHas('reviews', function ($query) {
                $query->whereHas('order', function ($query) {
                    $query->where('status', 'delivered');
                });
            })
            ->count();

        if ($reviewCount > 1000) {
            return __('message.review_count_above_1000');
        }

        return __('message.review_count', ['count' => $reviewCount ?? 0]);
    }
    public function getDeliveryFee(?int $vehicleId = null, ?float $distanceKm = null): ?int
    {
        $deliveryFeeService = app(\Modules\Order\Services\DeliveryFeeService::class);
        return $deliveryFeeService->calculateForStore($this, $vehicleId, $distanceKm);
    }

    /**
     * Get delivery fee for store display with currency information and dynamic calculation
     * Similar to CartResource's getDeliveryFeeForDisplay method
     */
    public function getDeliveryFeeForDisplay(?int $deliveryAddressId = null, ?int $vehicleId = null, ?float $userLat = null, ?float $userLng = null): array
    {
        $deliveryFeeService = app(\Modules\Order\Services\DeliveryFeeService::class);

        // Check for delivery address in request headers
        $deliveryAddressId = request()->header('address-id') ?? request()->header('AddressId');

        // Check for user coordinates in request headers
        $userLat = request()->header('lat');
        $userLng = request()->header('lng');

        // Calculate delivery fee based on available context
        $deliveryFee = $deliveryFeeService->calculateForCart($this, $deliveryAddressId, $vehicleId, $userLat, $userLng);

        // Get currency information
        $currencyCode = $this->getCurrencyCode();
        $currencyFactor = $this->getCurrencyFactor();

        return [
            'amount' => (int) $deliveryFee,
            'symbol_currency' => $currencyCode,
            'currency_factor' => $currencyFactor,
        ];
    }

    /**
     * Check if store can deliver to a specific address
     */
    public function canDeliverTo(int $addressId): bool
    {
        \Log::info("From Store canDeliverTo - " . $addressId);
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
        return Zone::whereHas('shippingPrices')
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
    public function getServiceFeePercentage(): float
    {
        return $this->storeSetting->service_fee_percentage ?? 0.0;
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
    public function zoneCourierToCover()
    {
        return $this->hasManyThrough(
            \Modules\Zone\Models\Zone::class,
            \Modules\Couier\Models\Couier::class,
            'store_id', // Foreign key on couiers table
            'id',       // Foreign key on zones table  
            'id',       // Local key on stores table
            'id'        // Local key on couiers table
        )->distinct();
    }

    /**
     * Get the currency factor for this store
     *
     * @return int
     */
    public function getCurrencyFactor(): int
    {
        // Return the stored currency factor or default to 100
        return $this->currency_factor ?? $this->address?->zone?->city?->governorate?->country?->currency_factor ?? 100;
    }
    public function getCurrencyCode(): string
    {
        return $this->currency_code ?? $this->address?->zone?->city?->governorate?->country?->code ?? 'USD';
    }

    /**
     * Check if the store is open now based on working days and is_closed flag
     */
    public function isOpenNow(): bool
    {
        if ($this->is_closed) {
            return false;
        }

        $currentDay = date('w'); // 0 = Sunday, 6 = Saturday
        $workingDay = $this->workingDays()->where('day_of_week', $currentDay)->first();

        if (!$workingDay) {
            return false;
        }

        $now = now()->format('H:i:s');
        return $now >= $workingDay->open_time && $now <= $workingDay->close_time;
    }
    public function workingHours(): ?string
    {
        $workingDay = $this->workingDays()->where('day_of_week', date('w'))->first();

        if (!$workingDay) {
            return null;
        }

        return $workingDay->open_time . ' - ' . $workingDay->close_time;
    }
    protected static function booted()
    {
        $handleStatusChanges = function ($store) {
            if ($store->isDirty('is_closed') && $store->is_closed) {
                $store->active_status = "close";
            }
            if ($store->isDirty('is_closed') && !$store->is_closed) {
                $store->active_status = "free";
            }
            if ($store->isDirty('active_status')) {
                $store->is_closed = $store->active_status === "close";
            }
        };
        static::updating($handleStatusChanges);
    }
    public function withdrawals()
    {
        return $this->morphMany(\Modules\Withdrawal\Models\Withdrawal::class, 'withdrawable');
    }
    public function getCategoriesString()
    {
        if (!$this->relationLoaded('categories')) {
            return '';
        }

        return $this->categories
            ->pluck('name')
            ->filter()
            ->unique()
            ->implode(', ');
    }
}
