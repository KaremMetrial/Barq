<?php

namespace Modules\Order\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderTypeEnum;
use Modules\Cart\Models\Cart;
use Modules\User\Models\User;
use Modules\AddOn\Models\AddOn;
use Modules\Admin\Models\Admin;
use Modules\Store\Models\Store;
use App\Enums\PaymentStatusEnum;
use Modules\Couier\Models\Couier;
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
}
