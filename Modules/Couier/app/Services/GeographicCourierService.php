<?php

namespace Modules\Couier\Services;

use Modules\Couier\Models\Couier;
use Modules\Couier\Models\CourierShiftTemplate;
use Modules\Couier\Models\CouierShift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeographicCourierService
{
    /**
     * Find nearest available couriers within radius
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param float $radiusKm Radius in kilometers
     * @param int $maxResults Maximum number of results
     * @return Collection
     */
    public function findNearestCouriers(float $lat, float $lng, float $radiusKm = 5.0, int $maxResults = 10): Collection
    {
        // Using Haversine formula for distance calculation
        $query = DB::table('couiers')
            ->select([
                'couiers.*',
                DB::raw("(
                    6371 * acos(
                        cos(radians(?)) *
                        cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(couiers.current_location, '$.lat')) AS DECIMAL(10,8)))) *
                        cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(couiers.current_location, '$.lng')) AS DECIMAL(11,8))) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(couiers.current_location, '$.lat')) AS DECIMAL(10,8))))
                    )
                ) AS distance_km")
            ])
            ->setBindings([$lat, $lng, $lat], 'select')
            ->where('status', 'active')
            ->where('avaliable_status', 'available')
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km', 'asc')
            ->take($maxResults * 2); // Get more to filter later

        $couriers = collect($query->get())->map(function ($courier) {
            return Couier::find($courier->id);
        });

        return $this->filterByActiveShift($couriers);
    }

    /**
     * Find couriers with active shifts within radius
     */
    public function findNearestActiveCouriers(float $lat, float $lng, float $radiusKm = 5.0): Collection
    {
        return collect();
    }

    /**
     * Advanced courier search with multiple factors
     */
    public function findOptimalCouriers(
        float $pickupLat,
        float $pickupLng,
        float $deliveryLat = null,
        float $deliveryLng = null,
        array $criteria = []
    ): Collection {
        $defaults = [
            'radius_km' => $criteria['radius_km'] ?? 5.0,
            'max_results' => $criteria['max_results'] ?? 10,
            'max_load' => $criteria['max_load'] ?? 3, // Max active orders per courier
            'priority' => $criteria['priority'] ?? 'balanced', // distance, rating, load
        ];

        $couriers = $this->findNearestActiveCouriers($pickupLat, $pickupLng, $defaults['radius_km']);

        // Filter by current load
        $couriers = $this->filterByCurrentLoad($couriers, $defaults['max_load']);

        // Score and sort couriers
        $couriers = $this->scoreAndRankCouriers($couriers, $defaults['priority'], [
            'pickup_lat' => $pickupLat,
            'pickup_lng' => $pickupLng,
            'delivery_lat' => $deliveryLat,
            'delivery_lng' => $deliveryLng,
        ]);

        return $couriers->take($defaults['max_results']);
    }

    /**
     * Filter couriers by current active assignments
     */
    private function filterByCurrentLoad(Collection $couriers, int $maxLoad): Collection
    {
        return $couriers->filter(function ($courier) use ($maxLoad) {
            $activeAssignments = $courier->assignments()->active()->count();
            return $activeAssignments < $maxLoad;
        });
    }

    /**
     * Filter couriers by active shift availability
     */
    private function filterByActiveShift(Collection $couriers): Collection
    {
        // For now, just check if courier has any assigned shift templates for today
        // This can be enhanced to check specific time constraints
        return $couriers->filter(function ($courier) {
            return $courier->hasActiveAssignments();
        });
    }

    /**
     * Score and rank couriers based on multiple factors
     */
    private function scoreAndRankCouriers(Collection $couriers, string $priority, array $orderCoords): Collection
    {
        return $couriers->map(function ($courier) use ($priority, $orderCoords) {
            $score = 0;

            // Distance factor (lower distance = higher score)
            $distanceScore = max(0, 100 - ($courier->distance_km * 10)); // Max 100 points for ≤10km
            $score += $distanceScore * 0.4; // 40% weight

            // Rating score
            $ratingScore = ($courier->avg_rate ?? 0) * 20; // Max 100 points for 5-star rating
            $score += $ratingScore * 0.3; // 30% weight

            // Current load score (lower load = higher score)
            $activeOrders = $courier->assignments()->active()->count();
            $loadScore = max(0, 100 - ($activeOrders * 20)); // Prefer less loaded couriers
            $score += $loadScore * 0.2; // 20% weight

            // Priority adjustments
            if ($priority === 'distance') {
                $score = ($distanceScore * 0.7) + ($ratingScore * 0.2) + ($loadScore * 0.1);
            } elseif ($priority === 'rating') {
                $score = ($ratingScore * 0.7) + ($distanceScore * 0.2) + ($loadScore * 0.1);
            } elseif ($priority === 'load') {
                $score = ($loadScore * 0.7) + ($distanceScore * 0.2) + ($ratingScore * 0.1);
            }

            $courier->scoring_data = [
                'total_score' => $score,
                'distance_score' => $distanceScore,
                'rating_score' => $ratingScore,
                'load_score' => $loadScore,
                'priority_factor' => $priority,
            ];

            return $courier;
        })->sortByDesc('scoring_data.total_score');
    }

    /**
     * Calculate optimized route for multiple orders
     */
    public function calculateOptimizedRoute(float $courierLat, float $courierLng, array $orderLocations): array
    {
        if (empty($orderLocations)) {
            return [];
        }

        // Simple nearest neighbor algorithm for route optimization
        // In production, this should use Google Maps Directions API or similar
        $remainingLocations = $orderLocations;
        $route = [];
        $currentLat = $courierLat;
        $currentLng = $courierLng;

        while (!empty($remainingLocations)) {
            $nearest = $this->findNearestLocation($currentLat, $currentLng, $remainingLocations);

            if (!$nearest) break;

            $route[] = $nearest;
            $currentLat = $nearest['lat'];
            $currentLng = $nearest['lng'];

            // Remove from remaining
            $remainingLocations = array_filter($remainingLocations, function ($loc) use ($nearest) {
                return !($loc['lat'] === $nearest['lat'] && $loc['lng'] === $nearest['lng']);
            });
        }

        return $route;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
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
     * Update courier's last known location
     */
    public function updateCourierLocation(int $courierId, float $lat, float $lng): bool
    {
        try {
            $courier = Couier::find($courierId);
            if (!$courier) return false;

            // In future, this should update a dedicated location table for tracking
            // For now, we can store in cache or a simple location field

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update courier location', [
                'courier_id' => $courierId,
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Find nearest location from current position
     */
    private function findNearestLocation(float $currentLat, float $currentLng, array $locations): ?array
    {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $currentLat,
                $currentLng,
                $location['lat'],
                $location['lng']
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $location;
                $nearest['_distance'] = $distance;
            }
        }

        return $nearest;
    }
}
