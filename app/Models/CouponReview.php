<?php

namespace App\Models;

use Modules\Coupon\Models\Coupon;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class CouponReview extends Model
{
    protected $fillable = [
        'coupon_id',
        'user_id',
        'rating',
        'comment',
        'status',
        'reviewed_at',
        'is_verified_purchase'
    ];

    protected $casts = [
        'rating' => 'integer',
        'reviewed_at' => 'datetime',
        'is_verified_purchase' => 'boolean'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}