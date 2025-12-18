<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class RatingKey extends Model
{
    use Translatable;

    protected $fillable = [
        'key',
        'is_active',
    ];

    public $translatedAttributes = ['label'];

    public function translations()
    {
        return $this->hasMany(RatingKeyTranslation::class);
    }

    public function reviewRatings()
    {
        return $this->hasMany(ReviewRating::class);
    }
}
