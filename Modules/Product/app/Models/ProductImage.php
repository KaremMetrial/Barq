<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'image_path',
        'is_primary',
        'product_id',
    ];
    protected $casts = [
        'is_primary' => 'boolean',
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
