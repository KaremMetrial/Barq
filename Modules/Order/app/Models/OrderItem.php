<?php

namespace Modules\Order\Models;
use Modules\AddOn\Models\AddOn;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderItem extends Model
{
    protected $fillable = [
        'quantity',
        'total_price',
        'order_id',
        'product_id',
        'option_id',
        'product_option_value_id'
    ];

    protected $casts = [
        'product_option_value_id' => 'array',
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    // Note: This relationship requires a pivot table 'order_item_option_values'
    // For now, we'll handle options through the JSON field instead
    // public function productOptionValues(): BelongsToMany
    // {
    //     return $this->belongsToMany(ProductOptionValue::class, 'order_item_option_values', 'order_item_id', 'product_option_value_id');
    // }

    // Keep the old method for backward compatibility but mark as deprecated
    /**
     * @deprecated Use productOptionValues() for multiple options
     */
    public function productOptionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class);
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'add_on_order_item')
            ->withPivot('quantity', 'price_modifier');
    }
}
