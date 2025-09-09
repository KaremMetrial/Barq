<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $table = 'sms_settings';

    protected $fillable = [
        'retry_count',
        'is_enable',
        'sms_provider_id',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function smsProvider()
    {
        return $this->belongsTo(SmsProvider::class);
    }
}
