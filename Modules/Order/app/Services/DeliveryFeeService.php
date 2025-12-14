<?php

namespace Modules\Order\Services;

use Modules\Store\Models\Store;
use Modules\Address\Models\Address;
use Modules\ShippingPrice\Models\ShippingPrice;


class DeliveryFeeService
{
    /**
     * Calculate delivery fee for an order
     */
    public function calculateForOrder(array $orderData, Store $store): float
    {
        // Check if free delivery is enabled
        if ($store->storeSetting && $store->storeSetting->free_delivery_enabled) {
            return 0.0;
        }

        // Check if delivery service is enabled
        if ($store->storeSetting && !$store->storeSetting->delivery_service_enabled) {
            return 0.0;
        }

        // Get delivery address if provided
        $deliveryAddressId = $orderData['delivery_address_id'] ?? null;
        if (!$deliveryAddressId) {
            return $this->getStoreDefaultFee($store);
        }

        // Validate delivery address and calculate fee
        try {
            $deliveryAddress = Address::find($deliveryAddressId);
            if (!$deliveryAddress || !$deliveryAddress->zone_id) {
                throw new \Exception('Invalid delivery address');
            }

            return $this->calculateFeeByDistance($store, $deliveryAddress);
        } catch (\Exception $e) {
            // Fallback to store default if address validation fails
            return $this->getStoreDefaultFee($store);
        }
    }

    /**
     * Calculate delivery fee for cart display
     */
    public function calculateForCart(Store $store, ?int $deliveryAddressId = null, ?int $vehicleId = null, ?float $userLat = null, ?float $userLng = null): float
    {
        // Check if free delivery is enabled
        if ($store->storeSetting && $store->storeSetting->free_delivery_enabled) {
            return 0.0;
        }

        // If user location provided, calculate based on coordinates
        if ($userLat && $userLng && !$deliveryAddressId) {
            return $this->calculateFeeByCoordinates($store, $userLat, $userLng, $vehicleId);
        }

        // If delivery address provided, calculate based on address
        if ($deliveryAddressId) {
            try {
                $deliveryAddress = Address::find($deliveryAddressId);
                if (!$deliveryAddress || !$deliveryAddress->zone_id) {
                    return $this->getStoreDefaultFee($store);
                }

                return $this->calculateFeeByDistance($store, $deliveryAddress, $vehicleId);
            } catch (\Exception $e) {
                return $this->getStoreDefaultFee($store);
            }
        }

        // No address/location provided, use store default
        return $this->getStoreDefaultFee($store);
    }

    /**
     * Calculate delivery fee for store display (no specific address)
     */
    public function calculateForStore(Store $store, ?int $vehicleId = null, ?float $distanceKm = null): ?float
    {
        // Check if free delivery is enabled
        if ($store->storeSetting && $store->storeSetting->free_delivery_enabled) {
            return 0.0;
        }

        // For branches, use the parent's zone for delivery fee calculation
        $store = $store->parent_id ? $store->parent : $store;
        $zoneId = $store->address?->zone_id;

        if (!$zoneId) {
            return $this->getStoreDefaultFee($store);
        }

        $shippingPrice = $this->getShippingPrice($zoneId, $vehicleId);
        if (!$shippingPrice) {
            return $this->getStoreDefaultFee($store);
        }

        $distanceKm = $distanceKm ?? 0;
        $fee = $shippingPrice->base_price + ($shippingPrice->per_km_price * $distanceKm);

        if ($shippingPrice->max_price && $fee > $shippingPrice->max_price) {
            $fee = $shippingPrice->max_price;
        }

        return round($fee, 3);
    }

    /**
     * Check if store can deliver to a specific address
     */
    public function canDeliverTo(Store $store, int $addressId): bool
    {
        \Log::info("From DeliveryFeeService canDeliverTo - " . $addressId);
        $address = Address::find($addressId);
        \Log::info("From DeliveryFeeService canDeliverTo Find - " . $address);
        if (!$address || !$address->zone_id) {  
            \Log::info("From DeliveryFeeService canDeliverTo False - " . $address);
            return false;
        }

        // Check if any shipping prices are configured in the system
        if (!ShippingPrice::exists()) {
            \Log::info("From DeliveryFeeService canDeliverTo False - " . $address);
            return false;
        }

        \Log::info("From DeliveryFeeService canDeliverTo True - " . $address);
        \Log::info("Check if store covers this zone through zoneToCover relationship - " . $store->zoneToCover()->where('zones.id', $address->zone_id)->exists());
        // Check if store covers this zone through zoneToCover relationship
        return $store->zoneToCover()->where('zones.id', $address->zone_id)->exists();
    }

    /**
     * Calculate fee based on distance between store and delivery address
     */
    private function calculateFeeByDistance(Store $store, Address $deliveryAddress, ?int $vehicleId = null): float
    {
        // For branches, use the parent's zone for delivery fee calculation
        $store = $store->parent_id ? $store->parent : $store;

        $storeAddress = $store->address;
        if (!$storeAddress || !$storeAddress->latitude || !$storeAddress->longitude) {
            return $this->getStoreDefaultFee($store);
        }

        if (!$deliveryAddress->latitude || !$deliveryAddress->longitude) {
            return $this->getStoreDefaultFee($store);
        }

        $distanceKm = $this->calculateDistance(
            $storeAddress->latitude,
            $storeAddress->longitude,
            $deliveryAddress->latitude,
            $deliveryAddress->longitude
        );

        $shippingPrice = $this->getShippingPrice($deliveryAddress->zone_id, $vehicleId);
        if (!$shippingPrice) {
            return $this->getStoreDefaultFee($store);
        }

        $minDistance = $shippingPrice->min_distance ?? 0;
        $basePrice = $shippingPrice->base_price;
        $perKmPrice = $shippingPrice->per_km_price;

        $fee = $basePrice;
        if ($distanceKm > $minDistance) {
            $fee += $perKmPrice * ($distanceKm - $minDistance);
        }

        if ($shippingPrice->max_price && $fee > $shippingPrice->max_price) {
            $fee = $shippingPrice->max_price;
        }

        return round($fee, 3);
    }

    /**
     * Calculate fee based on user coordinates
     */
    private function calculateFeeByCoordinates(Store $store, float $userLat, float $userLng, ?int $vehicleId = null): float
    {
        // For branches, use the parent's zone for delivery fee calculation
        $store = $store->parent_id ? $store->parent : $store;

        $storeAddress = $store->address;
        if (!$storeAddress || !$storeAddress->latitude || !$storeAddress->longitude) {
            return $this->getStoreDefaultFee($store);
        }

        $distanceKm = $this->calculateDistance(
            $storeAddress->latitude,
            $storeAddress->longitude,
            $userLat,
            $userLng
        );

        // Find zone by coordinates to get shipping price
        $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates($userLat, $userLng);
        if (!$zone) {
            return $this->getStoreDefaultFee($store);
        }

        $shippingPrice = $this->getShippingPrice($zone->id, $vehicleId);
        if (!$shippingPrice) {
            return $this->getStoreDefaultFee($store);
        }

        $minDistance = $shippingPrice->min_distance ?? 0;
        $basePrice = $shippingPrice->base_price;
        $perKmPrice = $shippingPrice->per_km_price;

        $fee = $basePrice;
        if ($distanceKm > $minDistance) {
            $fee += $perKmPrice * ($distanceKm - $minDistance);
        }

        if ($shippingPrice->max_price && $fee > $shippingPrice->max_price) {
            $fee = $shippingPrice->max_price;
        }

        return round($fee, 3);
    }

    /**
     * Get shipping price for zone and vehicle
     */
    private function getShippingPrice(int $zoneId, ?int $vehicleId = null): ?ShippingPrice
    {
        $query = ShippingPrice::where('zone_id', $zoneId);
        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }
        return $query->first();
    }

    /**
     * Get store's default delivery fee
     */
    private function getStoreDefaultFee(Store $store): float
    {
        return round($store->storeSetting?->delivery_fee ?? 10.00, 3);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radius of the earth in km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
