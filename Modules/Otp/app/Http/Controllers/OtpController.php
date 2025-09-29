<?php

namespace Modules\Otp\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Otp\Services\OtpService;
use Modules\Otp\Http\Requests\SendOtpRequest;
use Modules\Otp\Http\Requests\VerifyOtpRequest;

class OtpController extends Controller
{
    use ApiResponse;

    public function __construct(protected OtpService $otpService) {}

    public function sendOtp(SendOtpRequest $request)
    {
        $otpData = $this->otpService->sendOtp($request->validated());

        return $this->successResponse([
            'otp' => $otpData['otp'],
            'phone' => $otpData['phone'],
            'model_type' => $otpData['model_type'],
            'otp_expires_at' => $otpData['otp_expires_at'],
        ], 'OTP sent successfully');
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $result = $this->otpService->verifyOtpAndGetToken($request->validated());

        if (! $result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse(
            [
                'token' => $result['token']
            ],
            $result['message']
        );
    }
}
