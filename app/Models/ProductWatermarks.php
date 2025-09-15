<?php

namespace App\Models;

use App\Enums\ProductWatermarkPositionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWatermarks extends Model
{
    protected $fillable = [
        'image_url',
        'position',
        'opacity',
        'product_id',
    ];
    protected $casts = [
        'position' => ProductWatermarkPositionEnum::class,
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
