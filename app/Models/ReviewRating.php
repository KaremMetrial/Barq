<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewRating extends Model
{
    protected $fillable = [
        'review_id',
        'rating_key_id',
        'rating',
    ];

    public function review()
    {
        return $this->belongsTo(\Modules\Review\Models\Review::class, 'review_id', 'id');
    }

    public function ratingKey()
    {
        return $this->belongsTo(RatingKey::class);
    }
}
