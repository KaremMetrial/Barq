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

    protected $casts = [
        'is_group_order' => 'boolean',
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
    // إزالة علاقة owner المكررة - استخدم علاقة user بدلاً منها

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

        $deliveryFeeService = app(\Modules\Order\Services\DeliveryFeeService::class);
        return $deliveryFeeService->calculateForCart($this->store, $deliveryAddressId, $vehicleId);
    }

    /**
     * Get delivery fee for cart display - ensures non-null return for UI consistency
     */
    public function getDeliveryFeeForDisplay(?int $deliveryAddressId = null, ?int $vehicleId = null, ?float $userLat = null, ?float $userLng = null): float
    {
        $deliveryFeeService = app(\Modules\Order\Services\DeliveryFeeService::class);
        return $deliveryFeeService->calculateForCart($this->store, $deliveryAddressId, $vehicleId, $userLat, $userLng);
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

    /**
     * نطاقات مفيدة للاستعلامات
     */
    public function scopeActive($query)
    {
        return $query->whereHas('items', function($q) {
            $q->where('quantity', '>', 0);
        });
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeGroupOrders($query)
    {
        return $query->where('is_group_order', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
