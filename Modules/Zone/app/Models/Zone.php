<?php
namespace Modules\Zone\Models;

use App\Models\ShippingPrice;
use Modules\City\Models\City;
use Modules\Store\Models\Store;
use Modules\Address\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Zone extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'city_id',
        'is_active',
        'area',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'area' => 'array'
    ];
    /*
        * Scopes Query to search by name
        */
    #[Scope]
    public function searchName(Builder $query, string $search): Builder
    {
        return $query->whereTranslationLike('name', "%{$search}%");
    }

    /*
        * Relationship To Governorate
        */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
    public function shippingPrices(): HasMany
    {
        return $this->hasMany(ShippingPrice::class);
    }
    /**
     * Scope to find zones that contain given coordinates using spatial query
     */
    public function scopeWithinCoordinates($query, float $latitude, float $longitude)
    {
        return $query->whereRaw("ST_Contains(area, ST_GeomFromText('POINT(? ?)'))", [$longitude, $latitude]);
    }

    public function scopeFilter($query, $filters): mixed
    {
        if (isset($filters['city_id']) && $filters['city_id'] != 0) {
            $query->where('city_id', $filters['city_id']);
        }
        if (isset($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }
        if (!auth('sanctum')->check())
        {
            $query->whereIsActive(true);
        }
        if (auth('sanctum')->check() && !auth('sanctum')->user()->can('admin'))
        {
            $query->whereIsActive(true);
        }
        return $query->latest();
    }
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_zone', 'zone_id', 'store_id');
    }
    public static function pointInPolygon($lat, $lng, $polygon): bool
    {
        $inside = false;
        $points = $polygon;
        $j = count($points) - 1;

        for ($i = 0; $i < count($points); $i++) {
            $xi = $points[$i]['long']; // longitude
            $yi = $points[$i]['lat']; // latitude
            $xj = $points[$j]['long'];
            $yj = $points[$j]['lat'];

            $intersect = (($yi > $lat) != ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }
    public static function findZoneByCoordinates(float $lat, float $lng): ?self
    {
        $zones = self::where('is_active', true)->get();
        foreach ($zones as $zone) {
            if (self::pointInPolygon($lat, $lng, $zone->area)) {
                return $zone;
            }
        }

        return null; // not found
    }
    public function getFullAddressAttribute(): ?string
    {
        $parts = [];
        if ($this->city && $this->city->name) $parts[] = $this->city->name;
        if ($this->governorate && $this->governorate->name) $parts[] = $this->governorate->name;
        if ($this->country && $this->country->name) $parts[] = $this->country->name;
        return implode(', ', $parts) ?: null;
    }
    public function governorate(): BelongsTo
    {
        return $this->city->governorate();
    }
    public function country(): BelongsTo
    {
        return $this->city->governorate->country();
    }

}
