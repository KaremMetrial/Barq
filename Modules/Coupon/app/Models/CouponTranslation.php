<?php

namespace Modules\Coupon\Models;

use Illuminate\Database\Eloquent\Model;

class CouponTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
