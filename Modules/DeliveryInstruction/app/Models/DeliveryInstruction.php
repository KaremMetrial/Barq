<?php

namespace Modules\DeliveryInstruction\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class DeliveryInstruction extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['title', 'description'];
    protected $translationForeignKey = 'instruction_id';

    protected $fillable = ['is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
