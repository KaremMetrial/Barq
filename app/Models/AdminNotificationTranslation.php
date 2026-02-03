<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminNotificationTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'title',
        'body',
    ];
}
