<?php


namespace Modules\Country\Models;

use Modules\City\Models\City;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Governorate\Models\Governorate;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Country extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name'];
    protected $fillable = [
        'code',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
         * Scopes Query to get Only Active Country
         */
    #[Scope]
    protected function active(Builder $query)
    {
        return $this->where('is_active', true);
    }

    /*
         * Relationship to Governorates
         */
    public function governorates(): HasMany
    {
        return $this->hasMany(Governorate::class);
    }

    /*
         * Relationship to Cities through Governorates
         */
    public function cities(): HasManyThrough
    {
        return $this->hasManyThrough(City::class, Governorate::class);
    }

    public function scopeFilter($query, $filters)
    {
        if (isset($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }
        if(!auth('admin')->check())
        {
            $query->whereIsActive(true);
        }
        return $query->latest();
    }
}
