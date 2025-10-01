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
}
