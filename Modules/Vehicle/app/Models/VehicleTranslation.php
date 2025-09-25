<?php

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'description'];
}
