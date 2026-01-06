<?php
/*
namespace Modules\Order\Services;

use Modules\Store\Models\Store;
use Modules\Address\Models\Address;
use Modules\Zone\Models\Zone;
use Modules\ShippingPrice\Models\ShippingPrice;

class DeliveryFeeService
{
    /**
     * Calculate delivery fee for an order
     */
//     public function calculateForOrder(array $orderData, Store $store): float
//     {
//         $deliveryAddressId = $orderData['delivery_address_id'] ?? null;

//         if (!$deliveryAddressId) {
//             return 0.0; // Pickup order
//         }

//         // Get delivery address
//         $deliveryAddress = Address::find($deliveryAddressId);
//         if (!$deliveryAddress || !$deliveryAddress->latitude || !$deliveryAddress->longitude) {
//             return 0.0;
//         }

//         // Get store address
//         $storeAddress = $store->address;
//         if (!$storeAddress || !$storeAddress->latitude || !$storeAddress->longitude) {
//             return 0.0;
//         }

//         // Calculate distance
//         $distance = $this->calculateDistance(
//             $storeAddress->latitude,
//             $storeAddress->longitude,
//             $deliveryAddress->latitude,
//             $deliveryAddress->longitude
//         );

//         // Get zone for delivery address
//         $zone = $deliveryAddress->zone;
//         if (!$zone) {
//             return 0.0;
//         }

//         // Get shipping price for this zone and vehicle (if specified)
//         $vehicleId = $orderData['vehicle_id'] ?? null;
//         $shippingPrice = $this->getShippingPrice($store->id, $zone->id, $vehicleId);

//         if (!$shippingPrice) {
//             return 0.0;
//         }

//         // Calculate based on distance
//         $deliveryFee = $this->calculateFee($shippingPrice, $distance);

//         return round($deliveryFee, 2);
//     }

//     /**
//      * Check if store can deliver to address
//      */
//     public function canDeliverTo(Store $store, int $addressId): bool
//     {
//         $deliveryAddress = Address::find($addressId);
//         if (!$deliveryAddress || !$deliveryAddress->latitude || !$deliveryAddress->longitude) {
//             return false;
//         }

//         $storeAddress = $store->address;
//         if (!$storeAddress || !$storeAddress->latitude || !$storeAddress->longitude) {
//             return false;
//         }

//         // Calculate distance
//         $distance = $this->calculateDistance(
//             $storeAddress->latitude,
//             $storeAddress->longitude,
//             $deliveryAddress->latitude,
//             $deliveryAddress->longitude
//         );

//         // Check store's delivery coverage
//         $storeSettings = $store->storeSetting;
//         if ($storeSettings && $storeSettings->max_delivery_distance) {
//             if ($distance > $storeSettings->max_delivery_distance) {
//                 return false;
//             }
//         }

//         // Check if zone is covered
//         $zone = $deliveryAddress->zone;
//         if (!$zone) {
//             return false;
//         }

//         // Check if store covers this zone
//         return $store->zones()->where('zones.id', $zone->id)->exists();
//     }

//     /**
//      * Get shipping price for store-zone-vehicle combination
//      */
//     private function getShippingPrice(int $storeId, int $zoneId, ?int $vehicleId = null): ?ShippingPrice
//     {
//         $query = ShippingPrice::where('store_id', $storeId)
//             ->where('zone_id', $zoneId)
//             ->where('is_active', true);

//         if ($vehicleId) {
//             $query->where('vehicle_id', $vehicleId);
//         }

//         return $query->first();
//     }

//     /**
//      * Calculate fee based on shipping price and distance
//      */
//     private function calculateFee(ShippingPrice $shippingPrice, float $distance): float
//     {
//         if ($shippingPrice->fee_type === 'fixed') {
//             return $shippingPrice->fee;
//         }

//         if ($shippingPrice->fee_type === 'per_km') {
//             return $shippingPrice->fee_per_km * $distance;
//         }

//         if ($shippingPrice->fee_type === 'tiered') {
//             return $this->calculateTieredFee($shippingPrice, $distance);
//         }

//         // Default to fixed fee
//         return $shippingPrice->fee ?? 0;
//     }

//     /**
//      * Calculate tiered fee
//      */
//     private function calculateTieredFee(ShippingPrice $shippingPrice, float $distance): float
//     {
//         $tiers = $shippingPrice->tiers ?? [];

//         foreach ($tiers as $tier) {
//             if ($distance >= $tier['min_distance'] && $distance <= $tier['max_distance']) {
//                 return $tier['fee'];
//             }
//         }

//         // Return base fee if no tier matches
//         return $shippingPrice->fee ?? 0;
//     }

//     /**
//      * Calculate distance between two points using Haversine formula
//      */
//     private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
//     {
//         $earthRadius = 6371; // Radius in km

//         $latDelta = deg2rad($lat2 - $lat1);
//         $lonDelta = deg2rad($lon2 - $lon1);

//         $a = sin($latDelta / 2) * sin($latDelta / 2) +
//             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
//             sin($lonDelta / 2) * sin($lonDelta / 2);

//         $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

//         return $earthRadius * $c;
//     }
// }
