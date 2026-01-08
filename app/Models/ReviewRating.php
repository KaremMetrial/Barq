<?php

namespace App\Models;

use Modules\Section\Models\Section;
use Illuminate\Database\Eloquent\Model;

class ReviewRating extends Model
{
    protected $fillable = [
        'review_id',
        'rating_key_id',
        'rating',
        'description',
    ];

    public function review()
    {
        return $this->belongsTo(\Modules\Review\Models\Review::class, 'review_id', 'id');
    }

    public function ratingKey()
    {
        return $this->belongsTo(RatingKey::class);
    }
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
