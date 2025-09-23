<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyInfoTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'generic_name',
        'common_use'
    ];
}
