<?php

namespace App\Models;

use Modules\Section\Models\Section;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;

class RatingKey extends Model
{
    use Translatable;

    protected $fillable = [
        'key',
        'is_active',
        'section_id',
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
        public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
