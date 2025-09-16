<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
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
