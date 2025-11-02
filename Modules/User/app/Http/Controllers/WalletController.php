<?php

namespace Modules\User\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\User\Services\LoyaltyService;
use App\Http\Resources\LoyaltyTransactionResource;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Get user wallet information including balance and loyalty points
     */
    public function index(): JsonResponse
    {
        $userId = auth('user')->id();
        $user = auth('user')->user();

        // Get loyalty information
        $loyaltyInfo = $this->loyaltyService->getUserLoyaltyInfo($userId);

        // Get recent transactions (last 10)
        $recentLoyaltyTransactions = $this->loyaltyService->getUserTransactionHistory($userId, 10);

        // Get recent transactions
        $recentTransactions = $user->transactions()->latest()->take(10)->get();

        $walletData = [
            'balance' => [
                'amount' => $user->balance,
                'currency_symbol' => 'KWD',
            ],
            'loyalty_points' => [
                'available_points' => $loyaltyInfo['available_points'],
                'total_points' => $loyaltyInfo['total_points'],
                'points_expire_at' => $loyaltyInfo['points_expire_at'],
                // 'settings' => $loyaltyInfo['settings'],
            ],
            'recent_loyalty_transactions' => LoyaltyTransactionResource::collection($recentLoyaltyTransactions),
            'recent_transactions' => $recentTransactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                ];
            })
        ];

        return $this->successResponse($walletData, __('message.success'));
    }
}
