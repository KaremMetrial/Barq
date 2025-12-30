<?php

namespace Modules\Couier\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Couier\Models\Couier;
use Modules\Zone\Models\Zone;

class CourierLocationCacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const LOCATION_KEY_PREFIX = 'courier_location:';
    const ZONE_COURIERS_KEY_PREFIX = 'zone_couriers:';
    const COURIER_ZONES_KEY_PREFIX = 'courier_zones:';

    /**
     * Update courier location in cache
     */
    public function updateCourierLocation(int $courierId, float $lat, float $lng, array $metadata = []): bool
    {
        try {
            $locationData = [
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $metadata['accuracy'] ?? null,
                'speed' => $metadata['speed'] ?? null,
                'heading' => $metadata['heading'] ?? null,
                'updated_at' => now()->toISOString(),
                'timestamp' => now()->timestamp,
            ];

            // Store individual courier location
            Cache::put(
                self::LOCATION_KEY_PREFIX . $courierId,
                $locationData,
                now()->addSeconds(self::CACHE_TTL)
            );

            // Update zone-based indexes
            $this->updateCourierZoneIndexes($courierId, $locationData);

            Log::info('Courier location cached', [
                'courier_id' => $courierId,
                'lat' => $lat,
                'lng' => $lng
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache courier location', [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get courier location from cache
     */
    public function getCourierLocation(int $courierId): ?array
    {
        return Cache::get(self::LOCATION_KEY_PREFIX . $courierId);
    }

    /**
     * Get all active couriers in a zone
     */
    public function getZoneCouriers(int $zoneId): array
    {
        return Cache::get(self::ZONE_COURIERS_KEY_PREFIX . $zoneId, []);
    }

    /**
     * Find nearest couriers to a location within zone
     */
    public function findNearestCouriersInZone(
        int $zoneId,
        float $lat,
        float $lng,
        float $radiusKm = 5.0,
        int $limit = 10
    ): array {
        $zoneCouriers = $this->getZoneCouriers($zoneId);

        if (empty($zoneCouriers)) {
            return [];
        }

        $nearestCouriers = [];

        foreach ($zoneCouriers as $courierId) {
            $location = $this->getCourierLocation($courierId);

            if (!$location) {
                continue;
            }

            $distance = $this->calculateDistance(
                $lat, $lng,
                $location['lat'], $location['lng']
            );

            if ($distance <= $radiusKm) {
                $nearestCouriers[] = [
                    'courier_id' => $courierId,
                    'distance' => $distance,
                    'location' => $location,
                ];
            }
        }

        // Sort by distance and limit
        usort($nearestCouriers, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return array_slice($nearestCouriers, 0, $limit);
    }

    /**
     * Remove courier from cache (when shift ends or goes offline)
     */
    public function removeCourierFromCache(int $courierId): void
    {
        // Remove individual location
        Cache::forget(self::LOCATION_KEY_PREFIX . $courierId);

        // Remove from zone indexes
        $this->removeCourierFromZoneIndexes($courierId);

        Log::info('Courier removed from cache', ['courier_id' => $courierId]);
    }

    /**
     * Update courier zone indexes
     */
    private function updateCourierZoneIndexes(int $courierId, array $locationData): void
    {
        $courier = Couier::find($courierId);

        if (!$courier) {
            return;
        }

        // Get courier's assigned zones
        $zones = $courier->zonesToCover()->pluck('id')->toArray();

        foreach ($zones as $zoneId) {
            $zoneCouriers = Cache::get(self::ZONE_COURIERS_KEY_PREFIX . $zoneId, []);

            // Add courier to zone if not already there
            if (!in_array($courierId, $zoneCouriers)) {
                $zoneCouriers[] = $courierId;
                Cache::put(
                    self::ZONE_COURIERS_KEY_PREFIX . $zoneId,
                    $zoneCouriers,
                    now()->addSeconds(self::CACHE_TTL)
                );
            }
        }

        // Update courier-to-zones mapping
        Cache::put(
            self::COURIER_ZONES_KEY_PREFIX . $courierId,
            $zones,
            now()->addSeconds(self::CACHE_TTL)
        );
    }

    /**
     * Remove courier from all zone indexes
     */
    private function removeCourierFromZoneIndexes(int $courierId): void
    {
        $zones = Cache::get(self::COURIER_ZONES_KEY_PREFIX . $courierId, []);

        foreach ($zones as $zoneId) {
            $zoneCouriers = Cache::get(self::ZONE_COURIERS_KEY_PREFIX . $zoneId, []);
            $zoneCouriers = array_diff($zoneCouriers, [$courierId]);

            if (!empty($zoneCouriers)) {
                Cache::put(
                    self::ZONE_COURIERS_KEY_PREFIX . $zoneId,
                    array_values($zoneCouriers),
                    now()->addSeconds(self::CACHE_TTL)
                );
            } else {
                Cache::forget(self::ZONE_COURIERS_KEY_PREFIX . $zoneId);
            }
        }

        Cache::forget(self::COURIER_ZONES_KEY_PREFIX . $courierId);
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'total_cached_couriers' => count(Cache::get('courier_locations', [])),
            'cache_ttl_seconds' => self::CACHE_TTL,
            'cache_driver' => config('cache.default'),
        ];
    }
}
