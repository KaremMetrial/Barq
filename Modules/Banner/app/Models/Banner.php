<?php

namespace Modules\Banner\Models;

use Modules\City\Models\City;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Banner extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['title'];
    protected $fillable = [
        'image',
        'link',
        'start_date',
        'end_date',
        'is_active',
        'city_id',
        'bannerable_id',
        'bannerable_type',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    public function bannerable(): MorphTo
    {
        return $this->morphTo();
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
