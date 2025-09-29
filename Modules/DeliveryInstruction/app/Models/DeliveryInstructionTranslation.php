<?php

namespace Modules\DeliveryInstruction\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryInstructionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['title', 'description'];
}
