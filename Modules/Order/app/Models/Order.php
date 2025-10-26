<?php

namespace Modules\Order\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderTypeEnum;
use App\Models\ShippingPrice;
use Modules\Cart\Models\Cart;
use Modules\User\Models\User;
use Modules\AddOn\Models\AddOn;
use Modules\Admin\Models\Admin;
use Modules\Store\Models\Store;
use App\Enums\PaymentStatusEnum;
use Modules\Couier\Models\Couier;
use Modules\Review\Models\Review;
use Modules\Address\Models\Address;
use Modules\PosShift\Models\PosShift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'order_number',
        'reference_code',
        'type',
        'status',
        'note',
        'is_read',
        'total_amount',
        'discount_amount',
        'paid_amount',
        'delivery_fee',
        'tax_amount',
        'service_fee',
        'payment_status',
        'otp_code',
        'requires_otp',
        'delivery_address',
        'tip_amount',
        'estimated_delivery_time',
        'delivered_at',
        'actioned_by',
        'store_id',
        'user_id',
        'courier_id',
        'deleted_at',
        'created_at',
        'updated_at',
        'pos_shift_id',
        'coupon_id'
    ];

    protected $casts = [
        'total_amount' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'paid_amount' => 'decimal:3',
        'delivery_fee' => 'decimal:3',
        'tax_amount' => 'decimal:3',
        'service_fee' => 'decimal:3',
        'requires_otp' => 'boolean',
        'is_read' => 'boolean',
        'tip_amount' => 'decimal:3',
        'estimated_delivery_time' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => OrderTypeEnum::class,
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatusEnum::class,
    ];
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function courier()
    {
        return $this->belongsTo(Couier::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addOns()
    {
        return $this->belongsToMany(AddOn::class);
    }

    public function actionedBy()
    {
        return $this->belongsTo(Admin::class, 'actioned_by');
    }

    public function posShift()
    {
        return $this->belongsTo(PosShift::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(Address::class, 'delivery_address');
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function orderProofs(): HasMany
    {
        return $this->hasMany(OrderProof::class);
    }
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    public function scopeFilter($query, $filters)
    {
        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $query->where('order_number', 'like', '%' . $filters['search'] . '%');
        })
        ->when(isset($filters['status']), function ($query) use ($filters) {
            $query->where('status', $filters['status']);
        });

        // Role-based filtering using proper authentication guards
        if (auth('admin')->check()) {
            // Admin sees all orders - no additional filtering
        } elseif (auth('vendor')->check()) {
            // Vendor sees only their store's orders
            $query->where('store_id', auth('vendor')->user()->store_id);
        } elseif (auth('user')->check()) {
            // User sees only their own orders
            $query->where('user_id', auth('user')->id());
        }

        return $query->latest();
    }
    public function getDeliveryFee(?int $vehicleId = null, ?float $distanceKm = null): ?float
    {
        $zoneId = $this->address?->zone_id;
        if (!$zoneId) {
            return null;
        }

        $shippingPriceQuery = ShippingPrice::where('zone_id', $zoneId);
        if ($vehicleId) {
            $shippingPriceQuery->where('vehicle_id', $vehicleId);
        }
        $shippingPrice = $shippingPriceQuery->first();

        if (!$shippingPrice) {
            return null;
        }

        $distanceKm = $distanceKm ?? 0;

        $fee = $shippingPrice->base_price + ($shippingPrice->per_km_price * $distanceKm);

        if ($shippingPrice->max_price && $fee > $shippingPrice->max_price) {
            $fee = $shippingPrice->max_price;
        }

        return round($fee, 2);
    }

}
