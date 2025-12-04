<?php

namespace Modules\AddOn\Models;

use Modules\Cart\Models\CartItem;
use Modules\Product\Models\Product;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use App\Enums\AddOnApplicableToEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Modules\Store\Models\Store;

class AddOn extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'price',
        'is_active',
        'applicable_to',
        'store_id',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'applicable_to' => AddOnApplicableToEnum::class,
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'add_on_product');
    }
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class,'add_on_order')
        ->withPivot('quantity','price');
    }
    public function cartItems(): BelongsToMany
    {
        return $this->belongsToMany(CartItem::class, 'add_on_cart_item', 'add_on_id', 'cart_item_id')
            ->withPivot('quantity', 'price_modifier');
    }
    public function orderItems(): BelongsToMany
    {
        return $this->belongsToMany(OrderItem::class, 'add_on_order_item')
            ->withPivot('quantity', 'price_modifier');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (!auth('admin')->check()) {
            $query->whereIsActive(true);
        }
        return $query->latest();
    }
}
