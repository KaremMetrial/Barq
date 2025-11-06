<?php

namespace Modules\Vehicle\Models;

use App\Models\ShippingPrice;
use Illuminate\Database\Eloquent\Model;
use Modules\Couier\Models\CouierVehicle;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Vehicle extends Model implements TranslatableContract
{
    use Translatable, Sluggable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = ['slug', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
    public function vehicles(): HasMany
    {
        return $this->hasMany(CouierVehicle::class);
    }
    public function shippingPrices(): HasMany
    {
        return $this->hasMany(ShippingPrice::class);
    }
    public function scopeFilter($query, $filters): mixed
    {
        if (isset($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }
        if (auth('sanctum')->check() && !auth('sanctum')->user()->can('admin'))
        {
            $query->whereIsActive(true);
        }
        return $query->latest();
    }
}
