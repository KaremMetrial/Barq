<?php

namespace Modules\Product\Traits;

trait DeliveryTimeTrait
{
    protected function calculateDynamicDeliveryTime($store, $deliveryTypeUnit, $userLat, $userLng) {
        $storeLat = $store->address->latitude;
        $storeLng = $store->address->longitude;

        $distance = $this->calculateDistance($userLat, $userLng, $storeLat, $storeLng);

        $deliverySpeed = 10;

        $deliveryTime = $distance / $deliverySpeed;

        if ($deliveryTypeUnit == \App\Enums\DeliveryTypeUnitEnum::MINUTE) {
            $deliveryTime *= 60;
        }

        $minDeliveryTime = ceil($deliveryTime * 0.8);
        $maxDeliveryTime = ceil($deliveryTime * 1.2);

        return [
            'min' => $minDeliveryTime,
            'max' => $maxDeliveryTime,
        ];
    }

    function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371;

        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance;
    }
}
