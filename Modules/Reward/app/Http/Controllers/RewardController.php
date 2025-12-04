<?php

namespace Modules\Reward\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Reward\Services\RewardService;
use Modules\Reward\Http\Requests\RedeemRewardRequest;
use Modules\Reward\Http\Resources\RewardResource;
use Modules\Reward\Http\Resources\RewardRedemptionResource;

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
            'pagination' => [
                'total' => $redemptions->total(),
                'per_page' => $redemptions->perPage(),
                'current_page' => $redemptions->currentPage(),
                'last_page' => $redemptions->lastPage(),
            ]
        ], __('message.success'));
    }
}
