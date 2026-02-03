<?php

namespace Modules\Reward\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Reward\Services\RewardService;
use Modules\Reward\Http\Requests\RedeemRewardRequest;
use Modules\Reward\Http\Resources\RewardResource;
use Modules\Reward\Http\Resources\RewardRedemptionResource;
use Modules\User\Http\Resources\UserResource;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
class RewardController extends Controller
{
    use ApiResponse;

    public function __construct(private RewardService $rewardService) {}

    /**
     * Display available rewards for the user
     */
    public function index()
    {
        $filters = request()->all();

        // Add user's country if available
        if (auth('sanctum')->check() && auth('sanctum')->user()->country_id) {
            $filters['country_id'] = auth('sanctum')->user()->country_id;
        }

        // Add user's points as max filter
        if (auth('sanctum')->check()) {
            $filters['max_points'] = auth('sanctum')->user()->loyalty_points;
        }

        $rewards = $this->rewardService->getAvailableRewards($filters);

        return $this->successResponse([
            'rewards' => RewardResource::collection($rewards),
        ], __('message.success'));
    }

    /**
     * Display the specified reward
     */
    public function show($id)
    {
        $reward = $this->rewardService->getRewardById($id);
        return $this->successResponse([
            'reward' => new RewardResource($reward),
        ], __('message.success'));
    }

    /**
     * Redeem a reward
     */
    public function redeem(RedeemRewardRequest $request, $id)
    {
        try {
            $userId = auth('sanctum')->id();
            $redemption = $this->rewardService->redeemReward($id, $userId);

            return $this->successResponse([
                'redemption' => new RewardRedemptionResource($redemption),
                'message' => 'Reward redeemed successfully!',
            ], __('message.success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get user's redemption history
     */
    public function myRedemptions()
    {
        $userId = auth('sanctum')->id();
        $redemptions = $this->rewardService->getUserRedemptions($userId);

        return $this->successResponse([
            'redemptions' => RewardRedemptionResource::collection($redemptions),
            'pagination' => new PaginationResource($redemptions),
        ], __('message.success'));
    }

    /**
     * Get dashboard stats (top users and rewards)
     */
    public function dashboard()
    {
        $stats = $this->rewardService->getDashboardStats();

        // Transform users and append extra data if needed
        $topSpenders = $stats['top_spenders']->mapWithKeys(function ($user, $key) {
            $data = (new UserResource($user))->resolve();
            $data['total_orders_amount'] = (int) $user->orders_sum_total_amount ?? 0;
            $data['rank'] = $key + 1;
            return ['top' . ($key + 1) => $data];
        });

        $topPointsUsers = $stats['top_points_users']->mapWithKeys(function ($user, $key) {
            $data = (new UserResource($user))->resolve();
            $data['total_orders_amount'] = 0;
            $data['rank'] = $key + 1;
            return ['top' . ($key + 1) => $data];
        });

        $loyalty = $stats['loyalty_reward'] ? new RewardResource($stats['loyalty_reward']) : null;
        $spending = $stats['spending_reward'] ? new RewardResource($stats['spending_reward']) : null;

        return $this->successResponse([
            'top_points_users' => $topPointsUsers,
            'top_spenders' => $topSpenders,
            'rewards' => ($loyalty || $spending) ? ['loyalty' => $loyalty, 'spending' => $spending] : null,
            'user_loyalty_points' =>  (int) auth()->user()->loyalty_points ?? '0',
            'user_spending_value' => (int) auth()->user()->spending_value ?? 0,
            'currency_symbol' => auth()->user()->getCurrencySymbol(),
            'currency_factor' => auth()->user()->getCurrencyFactor(),
        ], __('message.success'));
    }
    public function wallet(Request $request)
    {
        // Add user's country if available
        if (auth('sanctum')->check() && auth('sanctum')->user()->country_id) {
            $filters['country_id'] = auth('sanctum')->user()->country_id;
        }

        // Add user's points as max filter
        if (auth('sanctum')->check()) {
            $filters['max_points'] = auth('sanctum')->user()->loyalty_points;
        }
        $dataWallet = $filters;
        $dataWallet['type'] = 'wallet';
        $rewardWallet = $this->rewardService->getAvailableRewards($dataWallet);

        $dataCoupon = $filters;
        $dataCoupon['type'] = 'coupon';
        $rewardCoupon = $this->rewardService->getAvailableRewards($dataCoupon);

        return $this->successResponse([
            'wallet_rewards' => RewardResource::collection($rewardWallet),
            'coupon_rewards' => RewardResource::collection($rewardCoupon),
        ], __('message.success'));
    }
}
