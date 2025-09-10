<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $fillable = [
        'product_id',
        'option_id',
        'min_select',
        'max_select',
        'is_required',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(ProductValue::class);
    }
}
