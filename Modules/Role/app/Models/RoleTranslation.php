<?php

namespace Modules\Role\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
