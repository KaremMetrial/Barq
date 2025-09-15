<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPriceTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
