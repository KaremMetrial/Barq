<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\User\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;

class LoyaltyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Get user loyalty balance and settings
     */
    public function balance(): JsonResponse
    {
        $userId = auth('user')->id();

        $loyaltyInfo = $this->loyaltyService->getUserLoyaltyInfo($userId);

        return $this->successResponse($loyaltyInfo, __('message.success'));
    }

    /**
     * Get user loyalty transaction history
     */
    public function history(Request $request): JsonResponse
    {
        $userId = auth('user')->id();
        $limit = $request->get('limit', 20);

        $transactions = $this->loyaltyService->getUserTransactionHistory($userId, $limit);

        return $this->successResponse([
            'transactions' => $transactions
        ], 'Transaction history retrieved successfully');
    }

    /**
     * Validate points redemption for an order
     */
    public function validateRedemption(Request $request): JsonResponse
    {
        $request->validate([
            'points' => 'required|numeric|min:0',
            'order_total' => 'required|numeric|min:0',
        ]);

        $userId = auth('user')->id();
        $points = $request->points;
        $orderTotal = $request->order_total;

        $validation = $this->loyaltyService->validateRedemption($userId, $points, $orderTotal);

        if (!$validation['valid']) {
            return $this->errorResponse(
                'Redemption validation failed',
                $validation['errors'],
                422
            );
        }

        return $this->successResponse($validation, __('message.success'));
    }

    /**
     * Redeem points (typically called during order creation)
     */
    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'points' => 'required|numeric|min:0',
            'order_total' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $userId = auth('user')->id();
        $points = $request->points;
        $orderTotal = $request->order_total;
        $description = $request->description ?? 'Points redeemed for order';

        $validation = $this->loyaltyService->validateRedemption($userId, $points, $orderTotal);

        if (!$validation['valid']) {
            return $this->errorResponse(
                'Redemption validation failed',
                $validation['errors'],
                422
            );
        }

        $success = $this->loyaltyService->redeemPoints($userId, $points, $description);

        if (!$success) {
            return $this->errorResponse('Failed to redeem points', [], 500);
        }

        return $this->successResponse([
            'points_redeemed' => $points,
            'value_redeemed' => $validation['redemption_value'],
        ], 'Points redeemed successfully');
    }

    /**
     * Calculate redemption value for points
     */
    public function calculateRedemption(Request $request): JsonResponse
    {
        $request->validate([
            'points' => 'required|numeric|min:0',
        ]);

        $points = $request->points;
        $value = $this->loyaltyService->calculateRedemptionValue($points);

        return $this->successResponse([
            'points' => $points,
            'redemption_value' => $value,
        ], 'Redemption value calculated successfully');
    }
}
