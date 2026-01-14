<?php

namespace Modules\Couier\Services;

use Modules\Zone\Models\Zone;
use Modules\Couier\Models\Couier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Couier\Models\CourierOrderAssignment;

class CacheBasedOrderAssignmentService
{
    protected CourierLocationCacheService $locationCache;
    protected RealTimeCourierService $realtimeService;

    public function __construct(
        CourierLocationCacheService $locationCache,
        RealTimeCourierService $realtimeService
    ) {
        $this->locationCache = $locationCache;
        $this->realtimeService = $realtimeService;
    }

    private function getCouriersFromDatabase(int $zoneId, float $pickupLat, float $pickupLng): array
    {
        $zone = Zone::with('couriers')->find($zoneId);
        if (!$zone) {
            return [];
        }

        $nearestCouriers = [];
        foreach ($zone->couriers as $courier) {
            // Check if courier is available
            if (!$this->isCourierAvailable($courier)) {
                continue;
            }

            // For database fallback, we don't have location data, so treat all as at pickup location
            // This prioritizes availability over distance
            $nearestCouriers[] = [
                'courier_id' => $courier->id,
                'distance' => 0, // Assume at pickup for fallback
                'location' => null,
                'courier' => $courier // Add courier object for filtering
            ];
        }

        return array_slice($nearestCouriers, 0, 5); // Limit to 5
    }

    /**
     * Assign order to nearest available courier using cached locations
     */
    public function assignOrderToNearestCourier(array $orderData): ?CourierOrderAssignment
    {
        DB::beginTransaction();

        try {
            // Extract order details
            $pickupLat = $orderData['pickup_lat'];
            $pickupLng = $orderData['pickup_lng'];
            $deliveryLat = $orderData['delivery_lat'] ?? null;
            $deliveryLng = $orderData['delivery_lng'] ?? null;
            $zoneId = $orderData['zone_id'] ?? $this->determineZoneFromLocation($pickupLat, $pickupLng);

            if (!$zoneId) {
                Log::warning('Could not determine zone for order assignment', [
                    'pickup_lat' => $pickupLat,
                    'pickup_lng' => $pickupLng
                ]);
                return null;
            }

            // Default values
            $defaults = [
                'priority' => $orderData['priority_level'] ?? 'normal',
                'max_load' => $orderData['max_load'] ?? 3,
                'timeout_seconds' => $orderData['timeout_seconds'] ?? 120
            ];

            // Find nearest couriers in zone using cache
            $nearestCouriers = $this->locationCache->findNearestCouriersInZone(
                $zoneId,
                $pickupLat,
                $pickupLng,
                5.0, // 5km radius
                5   // Top 5 couriers
            );

            if (empty($nearestCouriers)) {
                Log::info('No couriers found in zone cache, falling back to database', ['zone_id' => $zoneId]);
                $nearestCouriers = $this->getCouriersFromDatabase($zoneId, $pickupLat, $pickupLng);
                if (empty($nearestCouriers)) {
                    Log::info('No couriers found in zone database either', ['zone_id' => $zoneId]);
                    return null;
                }
            }

            // Convert array to collection for processing
            $couriersCollection = new Collection($nearestCouriers);

            // Filter by current load
            $filteredCouriers = $this->filterByCurrentLoad($couriersCollection, $defaults['max_load']);

            if ($filteredCouriers->isEmpty()) {
                Log::warning('No couriers available within load limit', [
                    'zone_id' => $zoneId,
                    'max_load' => $defaults['max_load']
                ]);
                return null;
            }

            // Score and rank couriers
            $rankedCouriers = $this->scoreAndRankCouriers(
                $filteredCouriers,
                $defaults['priority'],
                [
                    'pickup_lat' => $pickupLat,
                    'pickup_lng' => $pickupLng,
                    'delivery_lat' => $deliveryLat,
                    'delivery_lng' => $deliveryLng,
                ]
            );

            // Try to assign to each courier in order
            foreach ($rankedCouriers as $courierData) {
                $assignment = $this->attemptAssignment(
                    $courierData['courier_id'],
                    $orderData,
                    $defaults['timeout_seconds']
                );
                if ($assignment) {
                    DB::commit();
                    return $assignment;
                }
            }

            DB::rollBack();
            Log::warning('Failed to assign order to any cached courier');
            return null;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cache-based order assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_data' => $orderData
            ]);
            return null;
        }
    }

    private function scoreAndRankCouriers(Collection $couriers, string $priority, array $orderCoords): Collection
    {
        return $couriers->map(function ($courierData) use ($priority, $orderCoords) {
            $courier = $courierData['courier'] ?? null;

            if (!$courier) {
                // For cached couriers without full model
                $courier = Couier::withCount(['assignments' => function($query) {
                    $query->active();
                }])->find($courierData['courier_id']);
            }

            if (!$courier) {
                return null;
            }

            $score = 0;

            // Distance factor (lower distance = higher score)
            $distance = $courierData['distance'] ?? 0;
            $distanceScore = max(0, 100 - ($distance * 10)); // Max 100 points for ≤10km
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

            // Add scoring data to the courierData array
            $courierData['scoring_data'] = [
                'total_score' => round($score, 2),
                'distance_score' => round($distanceScore, 2),
                'rating_score' => round($ratingScore, 2),
                'load_score' => round($loadScore, 2),
                'priority_factor' => $priority,
            ];

            return $courierData;
        })->filter()->sortByDesc(function ($courierData) {
            return $courierData['scoring_data']['total_score'];
        });
    }

    private function filterByCurrentLoad(Collection $couriers, int $maxLoad): Collection
    {
        return $couriers->filter(function ($courierData) use ($maxLoad) {
            $courier = $courierData['courier'] ?? null;

            if (!$courier) {
                // Load courier with active assignments count
                $courier = Couier::withCount(['assignments' => function($query) {
                    $query->active();
                }])->find($courierData['courier_id']);

                if (!$courier) {
                    return false;
                }
            }

            $activeAssignments = $courier->assignments()->active()->count();
            return $activeAssignments < $maxLoad;
        });
    }

    /**
     * Attempt to assign order to specific courier
     */
    private function attemptAssignment(int $courierId, array $orderData, int $timeoutSeconds = 120): ?CourierOrderAssignment
    {
        Log::info('Attempting assignment:', [
            'order_id' => $orderData['order_id'],
            'courier_id' => $courierId,
        ]);

        // Check if order is already assigned
        $existingAssignment = CourierOrderAssignment::where('order_id', $orderData['order_id'])
            ->whereIn('status', ['assigned', 'accepted', 'in_transit'])
            ->first();

        if ($existingAssignment) {
            Log::warning('Order already assigned', [
                'order_id' => $orderData['order_id'],
                'existing_courier_id' => $existingAssignment->courier_id,
                'existing_status' => $existingAssignment->status
            ]);
            return null;
        }

        try {
            // Create assignment
            $assignment = CourierOrderAssignment::create([
                'courier_id' => $courierId,
                'order_id' => $orderData['order_id'],
                'status' => 'assigned',
                'pickup_lat' => $orderData['pickup_lat'],
                'pickup_lng' => $orderData['pickup_lng'],
                'delivery_lat' => $orderData['delivery_lat'] ?? null,
                'delivery_lng' => $orderData['delivery_lng'] ?? null,
                'assigned_at' => now(),
                'expires_at' => now()->addSeconds($timeoutSeconds),
                'estimated_distance_km' => $this->calculateEstimatedDistance($orderData),
                'courier_shift_id' => $orderData['courier_shift_id'] ?? null,
            ]);

            // Only notify if assignment was successful
            $this->realtimeService->notifyOrderAssigned($courierId, $assignment);
            $this->realtimeService->notifyOrderAssignedToOrder($assignment->order_id, [
                'courier_id' => $courierId,
                'assignment_id' => $assignment->id,
            ]);
            $this->scheduleTimeoutHandling($assignment->id, $timeoutSeconds);

            return $assignment;

        } catch (\Exception $e) {
            Log::error('Failed to create assignment: ' . $e->getMessage(), [
                'order_id' => $orderData['order_id'],
                'courier_id' => $courierId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
        private function scheduleTimeoutHandling(int $assignmentId, int $timeoutSeconds): void
        {
            // Schedule a job to handle assignment timeout
            \App\Jobs\CourierAssignmentTimeoutJob::dispatch($assignmentId)
                ->delay(now()->addSeconds($timeoutSeconds));
        }

    public function handleTimeout(int $assignmentId): void
    {
        $assignment = CourierOrderAssignment::find($assignmentId);

        if (!$assignment || $assignment->status !== 'assigned') {
            return;
        }

        try {
            $assignment->update([
                'status' => 'timed_out',
                'completed_at' => now()
            ]);

            // Notify the courier
            $this->realtimeService->notifyOrderExpired(
                $assignment->courier_id,
                $assignment->order_id,
                'timeout'
            );

            // Try to reassign
            if ($this->shouldReassignOrder($assignment->order_id)) {
                $this->tryReassignment($assignment->order_id, $assignmentId);
            }

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to handle assignment timeout', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage()
            ]);
        }
    }
    private function shouldReassignOrder(int $orderId): bool
    {
        $activeAssignments = CourierOrderAssignment::where('order_id', $orderId)
            ->active()
            ->count();

        return $activeAssignments === 0; // Reassign only if no active assignments
    }
    private function tryReassignment(int $orderId, int $excludedAssignmentId = null): void
    {
        $assignment = CourierOrderAssignment::where('order_id', $orderId)->first();

        if (!$assignment) return;

        $orderData = [
            'order_id' => $orderId,
            'pickup_lat' => $assignment->pickup_lat,
            'pickup_lng' => $assignment->pickup_lng,
            'delivery_lat' => $assignment->delivery_lat,
            'delivery_lng' => $assignment->delivery_lng,
            'priority_level' => $assignment->priority_level,
        ];

        $this->assignOrderToNearestCourier($orderData);
    }

    /**
     * Calculate estimated distance between pickup and delivery
     */
    private function calculateEstimatedDistance(array $orderData): float
    {
        if (!isset($orderData['pickup_lat'], $orderData['pickup_lng'],
                   $orderData['delivery_lat'], $orderData['delivery_lng'])) {
            return 0.0;
        }

        // Simple haversine formula implementation
        $lat1 = deg2rad($orderData['pickup_lat']);
        $lon1 = deg2rad($orderData['pickup_lng']);
        $lat2 = deg2rad($orderData['delivery_lat']);
        $lon2 = deg2rad($orderData['delivery_lng']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $earthRadius = 6371; // Earth's radius in kilometers
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Check if courier is available for assignment
     */
    private function isCourierAvailable(?Couier $courier): bool
    {
        if (!$courier) {
            return false;
        }

        return $courier->status->value === 'active' &&
               $courier->avaliable_status->value === 'available' &&
               $courier->shifts()->where('is_open', true)->whereNull('end_time')->exists();
    }

    /**
     * Determine zone from coordinates
     */
    private function determineZoneFromLocation(float $lat, float $lng): ?int
    {
        // Check if the Zone model has a findZoneByCoordinates method
        if (method_exists(Zone::class, 'findZoneByCoordinates')) {
            $zone = Zone::findZoneByCoordinates($lat, $lng)->first();
            return $zone?->id;
        }

        // Alternative implementation: Check zones with polygon boundaries
        $zone = Zone::whereRaw('ST_Contains(boundary, POINT(?, ?))', [$lng, $lat])->first();
        if ($zone) {
            return $zone->id;
        }

        // Fallback: Find nearest zone
        $zone = Zone::select('*')
            ->selectRaw('ST_Distance_Sphere(
                POINT(longitude, latitude),
                POINT(?, ?)
            ) as distance', [$lng, $lat])
            ->orderBy('distance')
            ->first();

        return $zone?->id;
    }
}
