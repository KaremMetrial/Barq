<?php

namespace App\Models;

use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $fillable = [
        'coupon_id',
        'user_id',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function coupon()
    {
        return $this->belongsTo(\Modules\Coupon\Models\Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
