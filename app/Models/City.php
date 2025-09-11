<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }
}
