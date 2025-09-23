<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAllergenTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
