<?php

namespace App\Models;

use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Transaction extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['description'];
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
