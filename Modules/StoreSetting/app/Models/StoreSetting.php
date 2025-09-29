<?php

namespace Modules\StoreSetting\Models;

use Modules\Store\Models\Store;
use App\Enums\DeliveryTypeUnitEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSetting extends Model
{
        protected $fillable = [
        'orders_enabled',
        'delivery_service_enabled',
        'external_pickup_enabled',
        'product_classification',
        'self_delivery_enabled',
        'free_delivery_enabled',
        'minimum_order_amount',
        'delivery_time_min',
        'delivery_time_max',
        'delivery_type_unit',
        'tax_rate',
        'order_interval_time',
        'store_id',
        'service_fee_percentage'
    ];
    protected $casts = [
        'delivery_type_unit' => DeliveryTypeUnitEnum::class,
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
