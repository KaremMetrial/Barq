<?php

namespace Modules\Banner\Models;

use Illuminate\Database\Eloquent\Model;

class BannerTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['title'];
}
