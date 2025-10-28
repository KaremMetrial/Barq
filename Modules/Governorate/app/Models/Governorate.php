<?php

namespace Modules\Governorate\Models;

use Modules\City\Models\City;
use Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Governorate extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name'];
    protected $fillable = [
        'country_id',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean'
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
     * Relationship to Country
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    /*
     * Relationship to Cities
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
         public function scopeFilter($query, $filters)
    {
        return $query->latest();
    }
}
