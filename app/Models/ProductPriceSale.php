<?php

namespace App\Models;

use App\Enums\SaleTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceSale extends Model
{
    protected $fillable = [
        'sale_price',
        'sale_type',
    ];
    protected $casts = [
        'sale_type' => SaleTypeEnum::class,
    ];
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }
}
