<?php

namespace Modules\Category\Models;

use Modules\Store\Models\Store;
use Modules\Coupon\Models\Coupon;
use Modules\Country\Models\Country;
use Modules\Product\Models\Product;
use Modules\Section\Models\Section;
use Modules\Interest\Models\Interest;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Category extends Model implements TranslatableContract
{
    use Translatable, Sluggable;
    public $translatedAttributes = ['name'];

    protected $fillable = [
        'slug',
        'icon',
        'is_active',
        'sort_order',
        'is_featured',
        'parent_id',
        'store_id',
    ];
    protected $with = ['translations'];

    protected static function booted()
    {
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('sort_order', 'asc');
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'category_section');
    }
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'category_coupon', 'category_id', 'coupon_id');
    }
    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class);
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('sections', function ($q) use ($countryId) {
            $q->whereHas('country', function ($q) use ($countryId) {
                $q->where('countries.id', $countryId);
            });
        });
    }

    public function scopeFilter($query, $filters)
    {
        if (isset($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if(isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id'])->orWhere('store_id', null);
        }

        if (!auth('admin')->check()) {
            $query->whereIsActive(true);
            // if (isset($filters['store_id'])) {
            //     $query->where('store_id', $filters['store_id'])
            //     ->orWhereHas('products', function ($q) use ($filters) {
            //         $q->where('store_id', $filters['store_id']);
            //     });
            // }
        }
        if(auth('vendor')->check())
        {
            $query->where('store_id', auth('vendor')->user()->store_id)
                ->orWhereHas('products', function ($q) {
                    $q->where('store_id', auth('vendor')->user()->store_id);
                });
        }
        if(isset($filters['section_id'])){
            $query->whereHas('sections', function ($q) use ($filters) {
                $q->where('section_id', $filters['section_id']);
            });
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

        return $query;
    }
}
