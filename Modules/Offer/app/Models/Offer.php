<?php

namespace Modules\Offer\Models;

use App\Enums\OfferStatusEnum;
use App\Enums\SaleTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Offer extends Model
{
    protected $fillable = [
        'discount_type',
        'discount_amount',
        'start_date',
        'end_date',
        'is_flash_sale',
        'has_stock_limit',
        'stock_limit',
        'is_active',
        'status',
    ];
    protected $casts = [
        'discount_amount' => 'decimal:3',
        'status' => OfferStatusEnum::class,
        'discount_type' => SaleTypeEnum::class,
        'is_active' => 'boolean',
        'is_flash_sale' => 'boolean',
        'has_stock_limit' => 'boolean',
    ];
    public function offerable(): MorphTo
    {
        return $this->morphTo();
    }
}
