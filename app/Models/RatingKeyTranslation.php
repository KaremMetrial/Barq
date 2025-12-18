<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingKeyTranslation extends Model
{
    protected $fillable = [
        'rating_key_id',
        'locale',
        'label',
    ];

    public function ratingKey()
    {
        return $this->belongsTo(RatingKey::class);
    }
}
