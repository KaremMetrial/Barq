<?php

namespace Modules\User\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\User\Services\LoyaltyService;
use Modules\LoyaltySetting\Http\Resources\LoyaltyTransactionResource;

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
        $user = auth('user')->user();   

        // Get recent transactions (last 10)
        $recentLoyaltyTransactions = $this->loyaltyService->getUserTransactionHistory($user->id, 10);

        // Get recent transactions
        $recentTransactions = $user->transactions()->latest()->take(10)->get();

        $walletData = [
            'balance' => [
                'amount' => $user->balance,
                'currency_symbol' => $user->getCurrencySymbol(),
            ],
            'loyalty_points' => [
                'total_points' => $user->loyalty_points,
            ],
            'recent_loyalty_transactions' => LoyaltyTransactionResource::collection($recentLoyaltyTransactions),
            'recent_transactions' => $recentTransactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
                ];
            })
        ];

        return $this->successResponse($walletData, __('message.success'));
    }
}
