<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
