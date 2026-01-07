<?php

namespace Modules\Reward\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Reward\Services\RewardService;
use Modules\Reward\Http\Requests\CreateRewardRequest;
use Modules\Reward\Http\Requests\UpdateRewardRequest;
use Modules\Reward\Http\Resources\RewardResource;
use App\Http\Resources\PaginationResource;
use Modules\Reward\Http\Resources\RewardRedemptionResource;
use Modules\User\Http\Resources\UserResource;
use Modules\Reward\Models\Reward;

class AdminRewardController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private RewardService $rewardService) {}

    /**
     * Display a listing of rewards
     */
    public function index()
    {
        $this->authorize('viewAny', Reward::class);
        $rewards = $this->rewardService->getAllRewards(request()->all());
        return $this->successResponse([
            'rewards' => RewardResource::collection($rewards),
            'pagination' => new PaginationResource($rewards),
        ], __('message.success'));
    }

    /**
     * Store a newly created reward
     */
    public function store(CreateRewardRequest $request)
    {
        $this->authorize('create', Reward::class);
        $reward = $this->rewardService->createReward($request->all());
        return $this->successResponse([
            'reward' => new RewardResource($reward),
        ], __('message.success'));
    }

    /**
     * Display the specified reward
     */
    public function show($id)
    {
        $reward = $this->rewardService->getRewardById($id);
        $this->authorize('view', $reward);
        return $this->successResponse([
            'reward' => new RewardResource($reward),
        ], __('message.success'));
    }

    /**
     * Update the specified reward
     */
    public function update(UpdateRewardRequest $request, $id)
    {
        $reward = $this->rewardService->getRewardById($id);
        $this->authorize('update', $reward);
        $reward = $this->rewardService->updateReward($id, $request->all());
        return $this->successResponse([
            'reward' => new RewardResource($reward),
        ], __('message.success'));
    }

    /**
     * Remove the specified reward
     */
    public function destroy($id)
    {
        $reward = $this->rewardService->getRewardById($id);
        $this->authorize('delete', $reward);
        $this->rewardService->deleteReward($id);
        return $this->successResponse(null, __('message.success'));
    }

    /**
     * Get dashboard stats
     */
    public function dashboard()
    {
        $this->authorize('viewAny', Reward::class);
        $stats = $this->rewardService->getDashboardStats();

        // Transform users and append extra data if needed
        $topSpenders = $stats['top_spenders']->map(function ($user) {
            $data = (new UserResource($user))->resolve();
            $data['total_orders_amount'] = $user->orders_sum_total_amount ?? 0;
            return $data;
        });

        return $this->successResponse([
            'top_points_users' => UserResource::collection($stats['top_points_users']),
            'top_spenders' => $topSpenders,
            'loyalty_reward' => $stats['loyalty_reward'] ? new RewardResource($stats['loyalty_reward']) : null,
            'spending_reward' => $stats['spending_reward'] ? new RewardResource($stats['spending_reward']) : null,
        ], __('message.success'));
    }
    public function stats()
    {
        $this->authorize('viewAny', Reward::class);
        $stats = $this->rewardService->stats();
        return $this->successResponse([
            'stats' => $stats
        ]);
    }
    public function getAllRedemption()
    {
        $this->authorize('viewAny', Reward::class);
        $redemption  = $this->rewardService->getAllRedemption();
        return $this->successResponse([
            'redemption' => RewardRedemptionResource::collection($redemption),
            'pagination' => new PaginationResource($redemption)
        ]);
    }
    public function resetLoyality()
    {
        // Only super admin can reset loyalty points - this is a critical operation
        $this->authorize('delete', Reward::class);
        $this->rewardService->resetAllLoyaltyPoints();
        return $this->successResponse();
    }
}
