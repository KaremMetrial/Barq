<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    use ApiResponse;
    public function updateToken()
    {
        $token = auth('sanctum')->user()->currentAccessToken();
        $token->fcm_device = request()->input('fcm_device');
        $token->country_id = request()->input('country_id');
        $token->language_code = request()->input('language_code');
        $token->notification_active = request()->input('notification_active', true);
        $token->save();
        return $this->successResponse([
            'token' => $token->only(['fcm_device', 'country_id', 'language_code', 'notification_active'])
        ], __('message.success'));
    }
}
