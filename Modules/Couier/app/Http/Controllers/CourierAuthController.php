<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Couier\Http\Resources\CourierResource;
use Modules\Couier\Http\Requests\LoginCourierRequest;
use Modules\Couier\Http\Requests\RegisterCourierRequest;
use Modules\Couier\Http\Requests\UpdateCourierRequest;

class CourierAuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new courier.
     */
    public function register(RegisterCourierRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Set default values
        $data['status'] = $data['status'] ?? 'active';
        $data['available_status'] = $data['available_status'] ?? 'available';

        // Create courier
        $courier = \Modules\Couier\Models\Couier::create($data);

        // Generate token
        $token = $courier->generateToken($request->only('fcm_device'));

        return $this->successResponse([
            "courier" => new CourierResource($courier->load('store')),
            'token' => $token,
        ], __('message.success'));
    }

    /**
     * Authenticate courier.
     */
    public function login(LoginCourierRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Find courier by email or phone
        $courier = \Modules\Couier\Models\Couier::where('email', $credentials['email'])
            ->orWhere('phone', $credentials['email'])
            ->first();

        if (!$courier || !\Hash::check($credentials['password'], $courier->password)) {
            return $this->errorResponse(__('auth.failed'), 401);
        }

        // Generate token
        $token = $courier->generateToken($request->only('fcm_device'));

        return $this->successResponse([
            "courier" => new CourierResource($courier->load('store')),
            'token' => $token,
        ], __('message.success'));
    }

    /**
     * Logout the authenticated courier.
     */
    public function logout(): JsonResponse
    {
        auth('courier')->user()->currentAccessToken()->delete();
        return $this->successResponse(null, __('message.success'));
    }

    /**
     * Update the authenticated courier's profile.
     */
    public function updateProfile(UpdateCourierRequest $request): JsonResponse
    {
        $courier = auth('courier')->user();

        // Remove password from update data if empty
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $courier->update($data);

        return $this->successResponse([
            "courier" => new CourierResource($courier->load('store'))
        ], __('message.success'));
    }

    /**
     * Delete the authenticated courier's account.
     */
    public function deleteAccount(): JsonResponse
    {
        $courier = auth('courier')->user();

        // Delete the courier
        $courier->delete();

        // Delete current token
        $courier->currentAccessToken()->delete();

        return $this->successResponse(null, __('message.success'));
    }
}
