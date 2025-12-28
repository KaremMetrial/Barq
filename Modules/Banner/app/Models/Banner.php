<?php


    namespace Modules\Banner\Models;

    use Modules\City\Models\City;
    use Illuminate\Database\Eloquent\Model;
    use Astrotomic\Translatable\Translatable;
    use Illuminate\Database\Eloquent\Relations\MorphTo;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
    use Modules\Country\Models\Country;
    use Stevebauman\Location\Facades\Location;

    class Banner extends Model implements TranslatableContract
    {
        use Translatable;
        public $translatedAttributes = ['title'];
        protected $with = ['translations'];

        protected $fillable = [
            'image',
            'link',
            'start_date',
            'end_date',
            'is_active',
            'city_id',
            'bannerable_id',
            'bannerable_type',
        ];
        protected $casts = [
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
        public function bannerable(): MorphTo
        {
            return $this->morphTo();
        }
        public function city(): BelongsTo
        {
            return $this->belongsTo(City::class);
        }
    public function scopeFilter($query, $filters)
    {
        $country = null;
        $addressId = request()->header('address-id');
        $lat = request()->header('lat');
        $lng = request()->header('lng');

        if ($addressId) {
            $address = \Modules\Address\Models\Address::find($addressId);
            if ($address && $address->country_id) {
                $country = Country::find($address->country_id);
            }
        } elseif ($lat && $lng) {
            $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates($lat, $lng);
            if ($zone) {
                $country = $zone->country;
            }
        }

        if (!$country) {
            $country = $this->getCountryFromIp();
        }

        if (!$country) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
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
