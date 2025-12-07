<?php

namespace Modules\Product\Models;

use App\Models\Report;
use Modules\Tag\Models\Tag;
use Modules\Unit\Models\Unit;
use Modules\AddOn\Models\AddOn;
use Modules\Offer\Models\Offer;
use Modules\Store\Models\Store;
use App\Enums\ProductStatusEnum;
use Modules\Coupon\Models\Coupon;
use Modules\Review\Models\Review;
use Modules\Order\Models\OrderItem;
use Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Modules\Favourite\Models\Favourite;
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
        'barcode',
        'weight',
        'preparation_time',
        'preparation_time_unit'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'max_cart_quantity' => 'integer',
        'status' => ProductStatusEnum::class,
        'is_reviewed' => 'boolean',
        'is_vegetarian' => 'boolean',
        'is_featured' => 'boolean',
        'weight' => 'decimal:3',
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

    public function availabilities(): HasMany
    {
        return $this->hasMany(ProductAvailability::class);
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
    public function productNutrition(): HasOne
    {
        return $this->hasOne(ProductNutrition::class);
    }
    public function productAllergen(): HasMany
    {
        return $this->hasMany(ProductAllergen::class);
    }
    public function pharmacyInfo(): HasMany
    {
        return $this->hasMany(PharmacyInfo::class);
    }
    public function watermark(): HasOne
    {
        return $this->hasOne(ProductWatermarks::class);
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }
    public function optionValues()
    {
        return $this->hasManyThrough(
            ProductValue::class,
            ProductOption::class,
            'product_id',
            'option_id',
            'id',
            'id'
        );
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    public function scopeFilter($query, $filters)
    {
        $query
            ->withAvg('reviews', 'rating');
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereTranslationLike('name', '%' . $searchTerm . '%')
                ->orWhereTranslationLike('description', '%' . $searchTerm . '%');
        }

        $admin = auth('admin')->check();
        $vendor = auth('vendor')->check() ? auth('vendor')->user() : null;

        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id'])->orWhereHas('category', function ($q) use ($filters) {
                $q->where('parent_id', $filters['category_id']);
            });
        }
        if (isset($filters['weight'])) {
            $query->where('weight', $filters['weight']);
        }

        if ($admin) {
            return $query->latest();
        }

        if ($vendor && $vendor->store_id) {
            return  $query->where('store_id', $vendor->store_id);
        }

        if (!$admin) {
            $query->whereStatus(ProductStatusEnum::ACTIVE);
        }
        return $query->latest();
    }
    public function getAvgRateAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
    public function offers()
    {
        return $this->morphMany(Offer::class, 'offerable');
    }
    public function requiredOptions()
    {
        return $this->hasMany(ProductOption::class);
    }
    public function getStats(): array
    {        // Total orders (count of order items for this product)
        $totalOrders = $this->orderItems()
            ->whereHas('order', function ($query) {
                $query->where('status', \App\Enums\OrderStatus::DELIVERED);
            })
            ->count();

        // Total reviews
        $totalReviews = $this->reviews()->count();

        // Total rating (sum of all ratings)
        $totalRating = $this->reviews()->sum('rating');

        // Average rating
        $avgRating = $totalReviews > 0 ? round($totalRating / $totalReviews, 2) : 0;

        // Conversion rate (معدل التحويل)
        // Calculate how many times product was added to cart (all order items, not just delivered)
        $totalCartAdditions = $this->orderItems()->count();

        // Conversion rate = (Completed Orders / Cart Additions) × 100
        // This shows what percentage of cart additions resulted in actual purchases
        $conversionRate = $totalCartAdditions > 0
            ? round(($totalOrders / $totalCartAdditions) * 100, 2)
            : 0;

        return [
            'total_orders' => $totalOrders,
            'total_reviews' => $totalReviews,
            'total_rating' => $totalRating,
            'average_rating' => $avgRating,
            'conversion_rate' => $conversionRate, // معدل التحويل (Completed Orders / Cart Additions)
        ];
    }
}
