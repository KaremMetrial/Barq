<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductValueTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'name',
        'locale',
    ];
}
