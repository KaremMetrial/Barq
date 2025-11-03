<?php

namespace Modules\PosTerminal\Models;

use Modules\Order\Models\Order;
use Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTerminal extends Model
{
    protected $table = "pos_terminals";
    protected $fillable = [
        "identifier",
        "name",
        "is_active",
        "store_id"
    ];
    protected $casts = [
        "is_active" => "boolean"
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function getLastSyncAttribute()
    {
        return $this->updated_at;
    }
    public function scopeCountOrders()
    {
        return $this->hasManyThrough(
            Order::class,
            Store::class,
            'id', // Foreign key on Store table...
            'store_id', // Foreign key on Order table...
            'store_id', // Local key on PosTerminal table...
            'id' // Local key on Store table...
        )->count();
    }
    public function scopeCountTodayOrders()
    {
        return $this->hasManyThrough(
            Order::class,
            Store::class,
            'id', // Foreign key on Store table...
            'store_id', // Foreign key on Order table...
            'store_id', // Local key on PosTerminal table...
            'id' // Local key on Store table...
        )->where('created_at', '=' , today())->count();
    }
    public function scopeAmountTodayOrders()
    {
        return $this->hasManyThrough(
            Order::class,
            Store::class,
            'id', // Foreign key on Store table...
            'store_id', // Foreign key on Order table...
            'store_id', // Local key on PosTerminal table...
            'id' // Local key on Store table...
        )->where('created_at', '=' , today())->sum('paid_amount');
    }

    // Accessors for resource
    public function getCountTodayOrdersAttribute()
    {
        return $this->hasManyThrough(
            Order::class,
            Store::class,
            'id', // Foreign key on Store table...
            'store_id', // Foreign key on Order table...
            'store_id', // Local key on PosTerminal table...
            'id' // Local key on Store table...
        )->where('orders.created_at', '>=', today())->count();
    }

    public function getAmountTodayOrdersAttribute()
    {
        return $this->hasManyThrough(
            Order::class,
            Store::class,
            'id', // Foreign key on Store table...
            'store_id', // Foreign key on Order table...
            'store_id', // Local key on PosTerminal table...
            'id' // Local key on Store table...
        )->where('orders.created_at', '>=', today())->sum('paid_amount');
    }

    public function scopeFilter($query, $filters)
    {

        return $query->latest();
    }

}
