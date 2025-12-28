<?php

namespace Modules\Section\Models;

use App\Enums\SectionTypeEnum;
use Modules\Country\Models\Country;
use Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Stevebauman\Location\Facades\Location;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Section extends Model implements TranslatableContract
{
    use Translatable, Sluggable;

    public $translatedAttributes = ['name', 'description'];

    protected $useTranslationFallback = true;
    protected $with = ['translations'];

    protected $fillable = [
        'slug',
        'icon',
        'is_restaurant',
        'is_active',
        'type',
        'is_show_on_home'
    ];

    protected $casts = [
        'is_restaurant' => 'boolean',
        'is_active' => 'boolean',
        'type' => SectionTypeEnum::class,
    ];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
    public function getProductCategories()
    {
        return Category::whereHas('products', function ($query) {
            $query->where('store_id', $this->id);
        })->get();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_section');
    }
    public function scopeFilter($query, $filters)
    {
        if (!auth('admin')->check()) {
            $query->whereIsActive(true)
            ->whereIsShowOnHome(true)
            ->where('type', '!=', SectionTypeEnum::DELIVERY_COMPANY);
        }
        if(isset($filters['type']) && $filters['type'] == 'store'){
            $query->where('type', '!=', SectionTypeEnum::DELIVERY_COMPANY);
        }
        // Filter by country if address-id is provided in header
        $country = null;
        $addressId = request()->header('address-id');
        $lat = request()->header('lat');
        $lng = request()->header('lng');

        if ($addressId) {
            $address = \Modules\Address\Models\Address::find($addressId);
            if ($address && $address->country_id) {
                $country = Country::find($address->country_id);
            }
        }elseif ($lat && $lng) {
            $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates($lat, $lng);
            if ($zone) {
                $country = $zone->country;
            }
        }

        if (!$country) {
           $country = $this->getCountryFromIp();
        }
        if (!$country) {
            $defaultCountryId = config('settings.default_country');
            $country = Country::find($defaultCountryId);
        }

        if (!$country) {
            $country = Country::first();
        }


        // $locationProvided = $addressId || ($lat && $lng);
        // $countryFound = false;

        // if ($addressId) {
        //     $address = \Modules\Address\Models\Address::find($addressId);
        //     if ($address && $address->country_id) {
        //         $query->whereHas('country', function ($q) use ($address) {
        //             $q->where('countries.id', $address->country_id);
        //         });
        //         $countryFound = true;
        //     }
        // } elseif ($lat && $lng) {
        //     // If lat/lng provided, find country by coordinates
        //     // Find zone by coordinates, then get country from zone's city->governorate->country
        //     $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates($lat, $lng);
        //     if ($zone && $zone->city && $zone->city->governorate && $zone->city->governorate->country) {
        //         $countryId = $zone->city->governorate->country->id;
        //         $query->whereHas('country', function ($q) use ($countryId) {
        //             $q->where('countries.id', $countryId);
        //         });
        //         $countryFound = true;
        //     }
        // }

        // If location provided but no country found, return no results
        // if ($locationProvided && !$countryFound) {
        //     $query->whereRaw('1 = 0');
        // }
        if ($country) {
            $query->whereHas('country', function ($q) use ($country) {
                $q->where('countries.id', $country->id);
            });
        }

        return $query->with('categories')->latest();
    }
    public function country()
    {
        return $this->belongsToMany(Country::class, 'country_section', 'section_id', 'country_id');
    }
    public function getCountryFromIp()
    {
        $position = Location::get(request()->ip());
        if ($position && isset($position->countryName)) {
            return Country::whereTranslationLike('name','%' . $position->countryName . '%')->first();
        }

        return null;
    }
}
