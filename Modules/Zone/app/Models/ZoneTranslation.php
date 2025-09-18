<?php

namespace Modules\Zone\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
