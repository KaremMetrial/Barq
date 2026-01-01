<?php

namespace Modules\City\Models;

use Modules\Zone\Models\Zone;
use Modules\Banner\Models\Banner;
use Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Governorate\Models\Governorate;
use function PHPUnit\Framework\throwException;
use Illuminate\Database\Eloquent\Attributes\Scope;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class City extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name'];

    protected $fillable = [
        'governorate_id',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
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
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }
    public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('governorate', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
        });
    }

    public function scopeFilter($query, $filters): mixed
    {
        if (isset($filters['governorate_id']) && $filters['governorate_id'] != 0) {
            $query->where('governorate_id', $filters['governorate_id']);
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
        if (auth('admin')->check()) {
            if (auth('admin')->user()->currentAccessToken()->country_id) {
                $country = Country::find(auth('admin')->user()->currentAccessToken()->country_id);
                if ($country) {
                    return $query->byCountry($country->id);
                }
            }
            return $query;
        }

        return $query->latest();
    }
}
