<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNutrition extends Model
{
    protected $fillable = [
        'calories',
        'fat',
        'protein',
        'carbohydrates',
        'sugar',
        'fiber',
        'product_id'
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
