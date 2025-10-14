<?php

namespace Modules\StoreSetting\Http\Resources;

use App\Enums\DeliveryTypeUnitEnum;
use App\Enums\StoreSettingStatusEnum;
use App\Enums\StoreSettingTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;

class StoreSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "orders_enabled"=> (bool) $this->orders_enabled,
            "delivery_service_enabled"=> (bool) $this->delivery_service_enabled,
            "external_pickup_enabled"=> (bool) $this->external_pickup_enabled,
            "product_classification"=> $this->product_classification,
            "self_delivery_enabled"=> (bool) $this->self_delivery_enabled,
            "free_delivery_enabled"=> (bool) $this->free_delivery_enabled,
            "minimum_order_amount"=> $this->minimum_order_amount,
            'delivery_time_max' => $this->delivery_time_max,
            'delivery_time_min' => $this->delivery_time_min,
            'delivery_type_unit' => $this->delivery_type_unit->value,
            'delivery_type_unit_label'=> DeliveryTypeUnitEnum::label($this->delivery_type_unit->value),
            'tax_rate' => $this->tax_rate,
            'service_fee_percentage' => $this->service_fee_percentage,
            'order_interval_time' => $this->order_interval_time,
            'store_id' => $this->store->id
        ];
    }
}
