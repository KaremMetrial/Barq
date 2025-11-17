<?php

namespace Modules\Order\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderStatusHistoryEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        "status",
        "changed_at",
        "note",
        "order_id"
    ];
    protected $casts = [
        "changed_at" => "datetime",
        "status" => OrderStatus::class,
    ];
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
