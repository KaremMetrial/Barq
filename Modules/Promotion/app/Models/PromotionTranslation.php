<?php

namespace Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'title',
        'description',
        'locale',
    ];
}
