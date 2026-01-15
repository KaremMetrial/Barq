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
use Modules\Section\Models\Section;
use Modules\LoyaltySetting\Models\LoyaltySetting;
use Modules\Reward\Models\Reward;

class Country extends Model implements TranslatableContract
{
    use Translatable;
    protected $with = ['translations'];

    public $translatedAttributes = ['name'];
    protected $fillable = [
        'code',
        'currency_symbol',
        'is_active',
        'currency_name',
        'flag',
        'currency_unit',
        'currency_factor',
        'service_fee_percentage',
        'tax_rate',
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
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%')->orWhere('code', '%' . $filters['search'] . '%');
        }
        if (!auth('sanctum')->check()) {
            $query->whereIsActive(true);
        }
        if (auth('sanctum')->check() && !auth('sanctum')->user()->can('admin')) {
            $query->whereIsActive(true);
        }
        return $query->latest();
    }
    public function section()
    {
        return $this->belongsToMany(Section::class, 'country_section', 'country_id', 'section_id');
    }
    public function loyaltySetting()
    {
        return $this->hasOne(LoyaltySetting::class);
    }
    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }
}
