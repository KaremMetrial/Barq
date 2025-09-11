<?php

namespace App\Models;

use App\Enums\AdStatusEnum;
use App\Enums\AdTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Ad extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['title', 'description'];

    protected $fillable = [
        'ad_number',
        'type',
        'is_active',
        'status',
        'start_date',
        'end_date',
        'media_path',
        'adable_type',
        'adable_id',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'type' => AdTypeEnum::class,
        'status' => AdStatusEnum::class,
    ];
    public function adable(): MorphTo
    {
        return $this->morphTo();
    }

}
