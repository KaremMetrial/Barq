<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAvailability extends Model
{
    protected $fillable = [
        'product_id',
        'store_id',
        'stock_quantity',
        'is_in_stock',
        'available_start_date',
        'available_end_date',
    ];
    protected $casts = [
        'is_in_stock' => 'boolean',
        'available_start_date' => 'date',
        'available_end_date' => 'date',
        'stock_quantity' => 'integer'
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\Modules\Store\Models\Store::class);
    }
}
