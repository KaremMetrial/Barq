<?php

namespace Modules\PaymentMethod\Models;

use Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\PaymentMethod\Database\Factories\PaymentMethodFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'is_cod',
        'config',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_cod' => 'boolean',
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to get active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // protected static function newFactory(): PaymentMethodFactory
    // {
    //     // return PaymentMethodFactory::new();
    // }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
