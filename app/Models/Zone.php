<?php
namespace App\Models;

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
}
