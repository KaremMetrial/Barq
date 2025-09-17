<?php

namespace Modules\Section\Models;

use Illuminate\Database\Eloquent\Model;

class SectionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'description'];
}
