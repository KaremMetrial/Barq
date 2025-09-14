<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'abbreviation'];
}
