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
use Modules\Order\Observers\OrderObserver;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Conversation\Models\Conversation;
use Modules\PaymentMethod\Models\PaymentMethod;
use Modules\Couier\Models\CourierOrderAssignment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([OrderObserver::class])]
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
        'delivery_address_id',
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
        'coupon_id',
        'payment_method_id'
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
        'estimated_delivery_time' => 'string',
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
        return $this->belongsTo(Couier::class, 'couier_id');
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
        return $this->belongsTo(Address::class, 'delivery_address_id');
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
        return $this->hasMany(Review::class, 'order_id', 'id');
    }
    public function scopeFilter($query, $filters)
    {
        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $query->where('order_number', 'like', '%' . $filters['search'] . '%');
        })
            ->when(isset($filters['from_date']), function ($query) use ($filters) {
                $query->where('created_at', '>=', $filters['from_date']);
            })
            ->when(isset($filters['to_date']), function ($query) use ($filters) {
                $query->where('created_at', '<=', $filters['to_date']);
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })->when(isset($filters['courier_id']), function ($query) use ($filters) {
                $query->where('couier_id', $filters['courier_id']);
            });

        $user = auth()->user();
        if ($user) {
            if ($user->tokenCan('admin')) {
                // Admin sees all orders - no additional filter
            } elseif ($user->tokenCan('vendor')) {
                // Vendor sees only their store's orders
                $query->where('store_id', $user->store_id);
            } elseif ($user->tokenCan('user')) {
                // User sees only their own orders
                $query->where('user_id', $user->id);
            } elseif ($user->tokenCan('courier')) {
                // Courier sees only orders assigned to them
                $query->where('couier_id', $user->id);
            } else {
                // Optional: prevent access if no proper ability
                $query->whereRaw('0 = 1'); // returns empty
            }
        }


        return $query->latest();
    }
    public function getDeliveryFee(?int $vehicleId = null, ?float $distanceKm = null): ?float
    {
        // Use store's delivery fee calculation method for consistency
        if (!$this->store) {
            return null;
        }

        return $this->store->getDeliveryFee($vehicleId, $distanceKm);
    }

    /**
     * Check if order is pickup type
     */
    public function isPickup(): bool
    {
        return $this->type === OrderTypeEnum::PICKUP;
    }

    /**
     * Check if order is delivery type
     */
    public function isDeliver(): bool
    {
        return $this->type === OrderTypeEnum::DELIVER;
    }

    /**
     * Check if order is service type
     */
    public function isService(): bool
    {
        return $this->type === OrderTypeEnum::SERVICE;
    }

    /**
     * Check if order is POS type
     */
    public function isPos(): bool
    {
        return $this->type === OrderTypeEnum::POS;
    }
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
    /**
     * Get order statistics with optional filtering
     *
     * @param int|null $storeId Filter by store ID
     * @param int|null $userId Filter by user ID
     * @return array
     */
    public static function getStats(?int $storeId = null, ?int $userId = null): array
    {
        $query = static::query();

        // Apply filters based on parameters
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ready_for_delivery,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as on_the_way,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled
        ', [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::READY_FOR_DELIVERY,
            OrderStatus::ON_THE_WAY,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED
        ])->first();

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'confirmed' => (int) $stats->confirmed,
            'processing' => (int) $stats->processing,
            'ready_for_delivery' => (int) $stats->ready_for_delivery,
            'on_the_way' => (int) $stats->on_the_way,
            'delivered' => (int) $stats->delivered,
            'cancelled' => (int) $stats->cancelled,
        ];
    }
        public function hasConsecutiveCancellations(int $count = 2): bool
    {
        if (!$this->user_id) {
            return false;
        }

        $recentOrders = $this->user->orders()
            ->latest('created_at')
            ->limit($count)
            ->get();

        // Check if we have enough orders
        if ($recentOrders->count() < $count) {
            return false;
        }

        // Check if the last 'count' orders are all cancelled
        $cancelledOrders = $recentOrders->take($count)->filter(function ($order) {
            return $order->status->value === 'cancelled';
        });

        return $cancelledOrders->count() === $count;
    }
    public function hasCancelledOrdersInLastMonth(int $count = 2): bool
    {
        if (!$this->user_id) {
            return false;
        }

        $cancelledOrdersInMonth = $this->user->orders()
            ->where('status', 'cancelled')
            ->where('created_at', '>=', now()->subMonth())
            ->count();

        return $cancelledOrdersInMonth >= $count;
    }
    public function wasCancelledByUser(): bool
    {
        if ($this->status->value !== 'cancelled') {
            return false;
        }

        $lastStatusHistory = $this->statusHistories()
            ->where('status', 'cancelled')
            ->latest('changed_at')
            ->first();

        if ($lastStatusHistory && $lastStatusHistory->changed_by) {
            return strpos($lastStatusHistory->changed_by, 'user:') === 0;
        }

        return false;
    }
    public function courierUnreadMessagesCount(): int
    {
        if (!$this->courier || !$this->user) {
            return 0;
        }

        $conversation = Conversation::where(function ($query) {
                $query->where('user_id', $this->user->id)
                      ->orWhere('store_id', $this->store->id);
            })
            ->where('couier_id', $this->courier->id)
            ->where('order_id', $this->id)
            ->where('type', 'delivery')
            ->first();

        if (!$conversation) {
            return 0;
        }

        return $conversation->messages()
            ->where('messageable_type', 'courier')
            ->where('messageable_id', $this->courier->id)
            ->where('is_read', false)
            ->count();
    }
    public function courierOrderAssignment()
    {
        return $this->hasMany(CourierOrderAssignment::class);
    }
}
