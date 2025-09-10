<?php

namespace App\Models;

use App\Enums\SaleTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Coupon extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name'];
    protected $fillable = [
        'code',
        'discount_amount',
        'discount_type',
        'usage_limit',
        'usage_limit_per_user',
        'minimum_order_amount',
        'start_date',
        'end_date',
        'is_active',
        'coupon_type',
        'object_type',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_amount' => 'decimal:3',
        'minimum_order_amount' => 'decimal:3',
        'discount_type' => SaleTypeEnum::class,
        'coupon_type' => CouponTypeEnum::class,
        'object_type' => ObjectTypeEnum::class,
    ];
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_coupon', 'coupon_id', 'category_id');
    }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product', 'coupon_id', 'product_id');
    }
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'coupon_store', 'coupon_id', 'store_id');
    }
}
