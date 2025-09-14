<?php

namespace App\Models;

use App\Enums\StoreStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Store extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;
    public $translatedAttributes = ['name'];

    protected $fillable = [
        'status',
        'note',
        'message',
        'logo',
        'cover_image',
        'phone',
        'is_featured',
        'is_active',
        'is_closed',
        'avg_rate',
        'section_id',
    ];
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'avg_rate' => 'float',
        'status' => StoreStatusEnum::class,
    ];
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
    public function workingDays(): HasMany
    {
        return $this->hasMany(WorkingDay::class);
    }
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_store', 'store_id', 'coupon_id');
    }
    public function CompaignParicipations(): HasMany
    {
        return $this->hasMany(CompaignParicipation::class, 'store_id');
    }
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
    public function couiers(): HasMany
    {
        return $this->hasMany(Couier::class);
    }
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }
    public function favourites(): MorphMany
    {
        return $this->morphMany(Favourite::class, 'favouriteable');
    }
}
