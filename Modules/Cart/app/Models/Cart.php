<?php

namespace Modules\Cart\Models;

use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\PosShift\Models\PosShift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cart extends Model
{
    protected $fillable = [
        "cart_key",
        "pos_shift_id",
        "store_id",
        "user_id",
        "is_group_order"
    ];
    // public function getRouteKeyName()
    // {
    //     return 'cart_key';
    // }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class);
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cart_user');
    }

    /**
     * Calculate delivery fee for this cart based on delivery address
     * This method should be used when we have a delivery address context
     */
    public function getCalculatedDeliveryFee(?int $deliveryAddressId = null, ?int $vehicleId = null): ?float
    {
        if (!$this->store) {
            return null;
        }

        // If no delivery address provided, use store's default delivery fee calculation
        if (!$deliveryAddressId) {
            return $this->store->getDeliveryFee($vehicleId);
        }

        // Calculate delivery fee based on distance from store to delivery address
        $deliveryAddress = \Modules\Address\Models\Address::find($deliveryAddressId);
        if (!$deliveryAddress || !$deliveryAddress->latitude || !$deliveryAddress->longitude) {
            return $this->store->getDeliveryFee($vehicleId);
        }

        $storeAddress = $this->store->address;
        if (!$storeAddress || !$storeAddress->latitude || !$storeAddress->longitude) {
            return $this->store->getDeliveryFee($vehicleId);
        }

        // Calculate distance between store and delivery address
        $distanceKm = $this->calculateDistance(
            $storeAddress->latitude,
            $storeAddress->longitude,
            $deliveryAddress->latitude,
            $deliveryAddress->longitude
        );

        return $this->store->getDeliveryFee($vehicleId, $distanceKm);
    }

    /**
     * Get delivery fee for cart display - ensures non-null return for UI consistency
     */
    public function getDeliveryFeeForDisplay(?int $deliveryAddressId = null, ?int $vehicleId = null): float
    {
        return $this->getCalculatedDeliveryFee($deliveryAddressId, $vehicleId) ?? 0.0;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
