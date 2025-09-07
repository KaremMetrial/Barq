<?php

namespace App\Models;

use App\Enums\SettingTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];
    protected $casts = [
        'type' => SettingTypeEnum::class,
    ];
}
