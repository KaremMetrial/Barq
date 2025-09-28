<?php

namespace Modules\Cart\Models;

use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\PosShift\Models\PosShift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        "cart_key",
        "pos_shift_id",
        "store_id",
        "user_id"
    ];
    public function getRouteKeyName()
    {
        return 'cart_key';
    }

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
}
