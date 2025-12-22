<?php

namespace Modules\Otp\Services;

use Carbon\Carbon;
use Modules\Otp\Models\Otp;
use Modules\User\Models\User;
use Modules\Couier\Models\Couier;
use Modules\Vendor\Models\Vendor;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Hash;
use Modules\Otp\Repositories\OtpRepository;
use Modules\User\Http\Resources\UserResource;

class OtpService
{
    protected array $modelTypeMap = [
        'user'    => User::class,
        'vendor'  => Vendor::class,
        'couier' => Couier::class,
    ];

    // Static Kuwait phone number for testing
    const TEST_PHONE_KWT = '12345678';

    public function __construct(protected OtpRepository $otpRepository, protected TwilioSmsService $smsService) {}

    /**
     * Send OTP - update or create OTP record and return OTP for testing.
     */
    public function sendOtp(array $data): array
    {
        $otpCode = $data['phone'] == self::TEST_PHONE_KWT ? 1111 : $this->generateOtpCode();

        $modelTypeClass = $this->getModelTypeClass($data['model_type']);
        if (!$modelTypeClass) {
            throw new \InvalidArgumentException('Invalid model_type.');
        }
        $phone = $data['phone'] == self::TEST_PHONE_KWT ? self::TEST_PHONE_KWT : $data['phone'];

        $updateOrCreateData = [
            'phone'        => $phone,
            'model_type'   => $modelTypeClass,
            'otp_verified' => false,
        ];
        if ($data['phone_code'] ?? null) {
            $updateOrCreateData['phone_code'] = $data['phone_code'];
        }

        $otp = $this->otpRepository->updateOrCreate(
            $updateOrCreateData,
            [
                'otp_hash'       => Hash::make( $otpCode),
                'otp_expires_at' => Carbon::now()->addMinutes(30),
                'otp'            => $otpCode,
            ]
        );
        $phone = $data['phone_code'] . $data['phone'];
        $this->smsService->sendOtp(
            $phone,
            $otpCode
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
            $modelTypeClass,
            $data['phone_code'] ?? null
        );

        if (! $isValid) {
            return [
                'success' => false,
                'token' => null,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Find user by phone
        $query = $modelTypeClass::where('phone', $data['phone']);
        if ($data['phone_code'] ?? null) {
            $query->where('phone_code', $data['phone_code']);
        }
        $user = $query->first();

        if (! $user) {
            return [
                'success' => true,
                'token' => null,
                'user' => null,
                'message' => 'OTP verified, but user account does not exist.',
            ];
        }
        if (request()->input('update_profile') != 'true') {
            $newToken = $user->createToken('auth_token',['user']);
            $newToken->accessToken->fcm_device = request()->input('fcm_device');
            $newToken->accessToken->save();
            $token = $newToken->plainTextToken;
        }
        return [
            'success' => true,
            'token' => $token ?? null,
            'user' => new UserResource($user),
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
