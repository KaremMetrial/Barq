<?php

namespace Modules\Governorate\Models;

use Illuminate\Database\Eloquent\Model;

class GovernorateTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'name',
    ];
}
