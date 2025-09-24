<?php

namespace Modules\Interest\Models;

use Illuminate\Database\Eloquent\Model;

class InterestTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
