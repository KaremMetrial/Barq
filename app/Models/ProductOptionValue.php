<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValue extends Model
{
    protected $fillable = [
        'product_value_id',
        'product_option_id',
        'stock',
        'is_default',
    ];
    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }
    public function productValue(): BelongsTo
    {
        return $this->belongsTo(ProductValue::class);
    }
    public function cartItems(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }
}
