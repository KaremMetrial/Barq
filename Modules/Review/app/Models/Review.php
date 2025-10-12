<?php

namespace Modules\Review\Models;

use Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{

    protected $fillable = [
        'rating',
        'comment',
        'food_quality_rating',
        'delivery_speed_rating',
        'order_execution_speed_rating',
        'product_quality_rating',
        'shopping_experience_rating',
        'overall_experience_rating',
        'delivery_driver_rating',
        'delivery_condition_rating',
        'match_price_rating',
        'image',
        'order_id',
        'reviewable_id',
        'reviewable_type',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewable()
    {
        return $this->morphTo();
    }

}
