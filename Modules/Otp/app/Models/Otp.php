<?php

namespace Modules\Otp\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'otp_hash',
        'model_type',
        'phone',
        'phone_code',
        'otp_expires_at',
        'otp_verified',
        'otp',
    ];
    protected $casts = [
        'otp_verified' => 'boolean',
    ];
    public static function validateOtp(string $phone, string $otp, string $modelType, string $phoneCode): bool
    {
        $record = self::where('phone', $phone)
            ->where('phone_code', $phoneCode)
            ->where('model_type', $modelType)
            ->where('otp_verified', false)
            ->where('otp_expires_at', '>=', now())
            ->first();
        if (!$record || !Hash::check($otp, $record->otp_hash)) {
            return false;
        }

        $record->update(['otp_verified' => true]);
        return true;
    }


}
