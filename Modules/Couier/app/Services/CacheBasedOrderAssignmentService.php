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
            $zoneId = $orderData['zone_id'] ?? $this->determineZoneFromLocation($pickupLat, $pickupLng);
            if (!$zoneId) {
                Log::warning('Could not determine zone for order assignment', [
                    'pickup_lat' => $pickupLat,
                    'pickup_lng' => $pickupLng
                ]);
                return null;
            }
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

            // Try to assign to each courier in order
            foreach ($nearestCouriers as $courierData) {
                $assignment = $this->attemptAssignment($courierData['courier_id'], $orderData);
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
                'order_data' => $orderData
            ]);
            return null;
        }
    }

    /**
     * Attempt to assign order to specific courier
     */
    private function attemptAssignment(int $courierId, array $orderData): ?CourierOrderAssignment
    {
        Log::info('data:', [
            'order_data' => $orderData,
            'courier_id' => $courierId,
        ]);

        // Check if order is already assigned
        $existingAssignment = CourierOrderAssignment::where('order_id', $orderData['order_id'])
            ->whereIn('status', ['assigned', 'accepted', 'in_progress'])
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
                'delivery_lat' => $orderData['delivery_lat'],
                'delivery_lng' => $orderData['delivery_lng'],
                'expires_at' => now()->addSeconds(120),
            ]);

            // Only notify if assignment was successful
            $this->realtimeService->notifyOrderAssigned($courierId, $assignment);
            $this->realtimeService->notifyOrderAssignedToOrder($assignment->order_id, [
                'courier_id' => $courierId,
                'assignment_id' => $assignment->id,
            ]);

            return $assignment;

        } catch (\Exception $e) {
            Log::error('Failed to create assignment: ' . $e->getMessage(), [
                'order_id' => $orderData['order_id'],
                'courier_id' => $courierId
            ]);
            return null;
        }
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
        // This should query zones table to find which zone contains these coordinates
        // For now, return a default zone or implement zone detection logic

        // Example implementation:
        $zone = Zone::findZoneByCoordinates($lat, $lng)->first();
        return $zone?->id;
    }
}
