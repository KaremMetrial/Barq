<?php

namespace Modules\Option\Models;

use Illuminate\Database\Eloquent\Model;

class OptionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
