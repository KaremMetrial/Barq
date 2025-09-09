<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddOnTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'description'];
}
