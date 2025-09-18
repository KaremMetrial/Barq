<?php

namespace Modules\Address\Models;

use Illuminate\Database\Eloquent\Model;

class AddressTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['address_line_1', 'address_line_2'];
}
