<?php

namespace Modules\Otp\Services;

use Carbon\Carbon;
use Modules\Otp\Models\Otp;
use Modules\User\Models\User;
use Modules\Couier\Models\Couier;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Modules\Otp\Repositories\OtpRepository;

class OtpService
{
    protected array $modelTypeMap = [
        'user'    => User::class,
        'vendor'  => Vendor::class,
        'couier' => Couier::class,
    ];

    public function __construct(protected OtpRepository $otpRepository) {}

    /**
     * Send OTP - update or create OTP record and return OTP for testing.
     */
    public function sendOtp(array $data): array
    {
        $otpCode = $this->generateOtpCode();

        $modelTypeClass = $this->getModelTypeClass($data['model_type']);
        if (!$modelTypeClass) {
            throw new \InvalidArgumentException('Invalid model_type.');
        }
        $otp = $this->otpRepository->updateOrCreate(
            [
                'phone'        => $data['phone'],
                'model_type'   => $modelTypeClass,
                'otp_verified' => false,
            ],
            [
                'otp_hash'       => Hash::make($otpCode),
                'otp_expires_at' => Carbon::now()->addMinutes(30),
                'otp'            => app()->environment('local', 'testing') ? $otpCode : null,
            ]
        );

        // For testing, return OTP (remove in production)
        return [
            'otp' => (int) $otpCode,
            'phone' => $otp->phone,
            'model_type' => $data['model_type'],
            'otp_expires_at' => $otp->otp_expires_at->diffForHumans(),
        ];
    }

    /**
     * Verify OTP and return token if user exists.
     */
    public function verifyOtpAndGetToken(array $data): array
    {
        $modelTypeClass = $this->getModelTypeClass($data['model_type']);
        if (! $modelTypeClass) {
            return [
                'success' => false,
                'token' => null,
                'message' => 'Invalid model_type',
            ];
        }

        $isValid = Otp::validateOtp(
            $data['phone'],
            $data['otp'],
            $modelTypeClass
        );

        if (! $isValid) {
            return [
                'success' => false,
                'token' => null,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Find user by phone
        $user = $modelTypeClass::where('phone', $data['phone'])->first();

        if (! $user) {
            return [
                'success' => true,
                'token' => null,
                'message' => 'OTP verified, but user account does not exist.',
            ];
        }
        if (!request()->has('update_profile') && request()->input('update_profile') != 'true') {
            $token = $user->createToken('auth_token')->plainTextToken;
        }
        return [
            'success' => true,
            'token' => $token,
            'message' => 'OTP verified and token generated.',
        ];
    }

    /**
     * Helper to get real model class from friendly string.
     */
    private function getModelTypeClass(string $modelType): ?string
    {
        return $this->modelTypeMap[strtolower($modelType)] ?? null;
    }

    /**
     * Generate numeric OTP code.
     */
    private function generateOtpCode()
    {
        return random_int(1000, 9999);
    }
}
