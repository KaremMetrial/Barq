<?php

namespace Modules\Product\Models;

use Modules\Option\Models\Option;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public function values(): HasMany
    {
        return $this->hasMany(ProductValue::class,'option_id');
    }
}
