<?php

namespace App\Models;

use App\Enums\ProductStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Product extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'is_active',
        'max_cart_quantity',
        'status',
        'note',
        'is_reviewed',
        'is_vegetarian',
        'is_featured',
        'store_id',
        'category_id',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'max_cart_quantity' => 'integer',
        'status' => ProductStatusEnum::class,
        'is_reviewed' => 'boolean',
        'is_vegetarian' => 'boolean',
        'is_featured' => 'boolean',
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
    public function price(): HasOne
    {
        return $this->hasOne(ProductPrice::class);
    }
    public function availability(): HasOne
    {
        return $this->hasOne(ProductAvailability::class);
    }
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'add_on_product');
    }
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product', 'product_id', 'coupon_id');
    }
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)
                ->withPivot('unit_value');
    }
    public function favourites(): MorphMany
    {
        return $this->morphMany(Favourite::class, 'favouriteable');
    }
}
