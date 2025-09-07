<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SmsProvider extends Model
{
    protected $fillable = [
        'name',
        'type',
        'api_url',
        'api_key',
        'api_secret',
        'sender_id',
        'sort_order',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function smsSettings(): HasOne
    {
        return $this->hasOne(SmsSetting::class);
    }
}
