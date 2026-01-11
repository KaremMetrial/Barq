<?php

namespace Modules\Review\Models;

use Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;
use App\Models\ReviewRating;

class Review extends Model
{

    protected $fillable = [
        'comment',
        'image',
        'order_id',
        'reviewable_id',
        'reviewable_type',
    ];

    public function reviewRatings()
    {
        return $this->hasMany(ReviewRating::class, 'review_id', 'id');
    }
    public function averageRating(): ?float
    {
        return $this->reviewRatings()->avg('rating');
    }
    public function ratingsForDisplay()
    {
        return $this->reviewRatings()->with('ratingKey')->get();
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function reviewable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this review (through the order)
     */
    public function user()
    {
        return $this->hasOneThrough(
            \Modules\User\Models\User::class,
            Order::class,
            'id',           // Foreign key on orders table
            'id',           // Foreign key on users table
            'order_id',     // Local key on reviews table
            'user_id'       // Local key on orders table
        );
    }
    public function scopeFilter($query, $filters)
    {
       return $query;
    }
}
