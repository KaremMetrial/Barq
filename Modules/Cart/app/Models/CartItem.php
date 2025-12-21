<?php

namespace Modules\Cart\Models;

use Modules\User\Models\User;
use Modules\AddOn\Models\AddOn;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CartItem extends Model
{
    protected $fillable = [
        "quantity",
        "total_price",
        "note",
        "cart_id",
        "product_id",
        "product_option_value_id",
        "is_group_order",
        "added_by_user_id",
    ];

    protected $casts = [
        'product_option_value_id' => 'array',
        'quantity' => 'integer',
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
    public function productOptionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class);
    }
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(
            AddOn::class,
            'add_on_cart_item',
            'cart_item_id',
            'add_on_id'
        )->withPivot('quantity', 'price_modifier');
    }
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
