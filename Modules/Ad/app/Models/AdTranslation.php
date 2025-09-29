<?php

namespace Modules\Ad\Models;

use Illuminate\Database\Eloquent\Model;

class AdTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['title', 'description'];
}
