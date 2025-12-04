<?php

namespace Modules\Address\Models;

use Modules\Zone\Models\Zone;
use Modules\City\Models\City;
use App\Enums\AddressTypeEnum;
use Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Modules\Governorate\Models\Governorate;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Address extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['address_line_1', 'address_line_2'];

    protected $fillable = [
        'latitude',
        'longitude',
        'name',
        'phone',
        'is_default',
        'type',
        'zone_id',
        'addressable_type',
        'addressable_id',
        'city_id',
        'governorate_id',
        'country_id',
        'apartment_number',
        'house_number',
        'street'
    ];
    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'type' => AddressTypeEnum::class,
    ];
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    public function scopeFilter($query, $filters)
    {
        $user = auth('user')->user();
        if ($user) {
            $query->where('addressable_id', $user->id)
                ->where('addressable_type', 'user');
        }
        return $query->latest();
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = [];
        if ($this->address_line_1) $parts[] = $this->address_line_1;
        if ($this->address_line_2) $parts[] = $this->address_line_2;
        if ($this->street) $parts[] = $this->street;
        if ($this->house_number) $parts[] = $this->house_number;
        if ($this->apartment_number) $parts[] = $this->apartment_number;
        if ($this->zone && $this->zone->name) $parts[] = $this->zone->name;
        if ($this->city && $this->city->name) $parts[] = $this->city->name;
        if ($this->governorate && $this->governorate->name) $parts[] = $this->governorate->name;
        if ($this->country && $this->country->name) $parts[] = $this->country->name;
        return implode(', ', $parts) ?: null;
    }
    protected static function booted()
    {
        $assignZoneFromCoordinates = function ($address) {
            // If coordinates are provided, always find and assign the zone
            if ($address->latitude && $address->longitude) {
                $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates($address->latitude, $address->longitude);
                if ($zone) {
                    $address->zone_id = $zone->id;
                    $address->city_id = $zone->city_id;
                    $address->governorate_id = $zone->city->governorate_id;
                    $address->country_id = $zone->city->governorate->country_id;
                }
            }
        };

        static::creating($assignZoneFromCoordinates);
        static::updating($assignZoneFromCoordinates);
    }
}
