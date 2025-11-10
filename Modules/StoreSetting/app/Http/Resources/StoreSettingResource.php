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
        $userLat = $request->input('lat');
        $userLng = $request->input('long');

        if ($userLat && $userLng && $this->relationLoaded('store') && $this->store->relationLoaded('address') && $this->store->address) {
            $storeLat = $this->store->address->latitude;
            $storeLng = $this->store->address->longitude;
            $distance = $this->calculateDistance($storeLat, $storeLng, $userLat, $userLng);
            $timeHours = $distance / 30; // 30 km/h
            $timeMinutes = $timeHours * 60;
            $delivery_time_min = max(0, round($timeMinutes - 5));
            $delivery_time_max = round($timeMinutes + 5);
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

    /**
     * Calculate distance between two points using Haversine formula.
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radius of the earth in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
