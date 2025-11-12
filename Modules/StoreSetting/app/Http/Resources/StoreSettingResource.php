<?php

namespace Modules\StoreSetting\Http\Resources;

use App\Enums\DeliveryTypeUnitEnum;
use App\Enums\StoreSettingStatusEnum;
use App\Enums\StoreSettingTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;
use Modules\Product\Traits\DeliveryTimeTrait;

class StoreSettingResource extends JsonResource
{
    use DeliveryTimeTrait;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
public function toArray(Request $request): array
    {
        $userLat = $request->header('lat');
        $userLng = $request->header('lng');

        if ($userLat && $userLng && $this->store && $this->store->address) {
            $deliveryTypeUnit = $this->delivery_type_unit ?? \App\Enums\DeliveryTypeUnitEnum::MINUTE;
            $dynamicDeliveryTimes = $this->calculateDynamicDeliveryTime($this->store, $deliveryTypeUnit, $userLat, $userLng);
            $delivery_time_min = $dynamicDeliveryTimes['min'];
            $delivery_time_max = $dynamicDeliveryTimes['max'];
        } else {
            $delivery_time_min = $this->delivery_time_min;
            $delivery_time_max = $this->delivery_time_max;
        }

        $delivery_type_unit = $this->delivery_type_unit->value ?? DeliveryTypeUnitEnum::MINUTE->value;
        $delivery_type_unit_label = DeliveryTypeUnitEnum::label($delivery_type_unit);

        return [
            "id"=> $this->id,
            "orders_enabled"=> (bool) $this->orders_enabled,
            "delivery_service_enabled"=> (bool) $this->delivery_service_enabled,
            "external_pickup_enabled"=> (bool) $this->external_pickup_enabled,
            "product_classification"=> $this->product_classification,
            "self_delivery_enabled"=> (bool) $this->self_delivery_enabled,
            "free_delivery_enabled"=> (bool) $this->free_delivery_enabled,
            "minimum_order_amount"=> number_format($this->minimum_order_amount,0),
            'delivery_time_max' => $delivery_time_max,
            'delivery_time_min' => $delivery_time_min,
            'delivery_type_unit' => $delivery_type_unit,
            'delivery_type_unit_label' => $delivery_type_unit_label,
            'delivery_time_range' => $delivery_time_min && $delivery_time_max ? $delivery_time_min . '-' . $delivery_time_max . ' ' . $delivery_type_unit_label : null,
            'tax_rate' =>number_format($this->tax_rate,0),
            'service_fee_percentage' => number_format($this->service_fee_percentage,0),
            'order_interval_time' => $this->order_interval_time,
            // 'store_id' => $this->store->id
        ];
    }


}
