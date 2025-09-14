<?php

namespace App\Models;

use App\Enums\NationalIdentityTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NationalIdentity extends Model
{
    protected $fillable = [
        'type',
        'national_id',
        'front_image',
        'back_image'
    ];
    protected $casts = [
        'type' => NationalIdentityTypeEnum::class
    ];
    public function identityable(): MorphTo
    {
        return $this->morphTo('identityable');
    }
}
