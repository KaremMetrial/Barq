<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderTypeEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'cart_id',
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
    public function add
}
