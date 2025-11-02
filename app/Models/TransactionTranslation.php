<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'description',
    ];
}
