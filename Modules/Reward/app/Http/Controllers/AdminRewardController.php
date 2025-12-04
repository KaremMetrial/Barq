<?php

namespace Modules\Reward\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Reward\Services\RewardService;
use Modules\Reward\Http\Requests\CreateRewardRequest;
use Modules\Reward\Http\Requests\UpdateRewardRequest;
use Modules\Reward\Http\Resources\RewardResource;
use App\Http\Resources\PaginationResource;

class AdminRewardController extends Controller
{
    use ApiResponse;

    public function __construct(private RewardService $rewardService) {}

    /**
     * Display a listing of rewards
     */
    public function index()
    {
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
        return $this->successResponse([
            'reward' => new RewardResource($reward),
        ], __('message.success'));
    }

    /**
     * Update the specified reward
     */
    public function update(UpdateRewardRequest $request, $id)
    {
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
        $this->rewardService->deleteReward($id);
        return $this->successResponse(null, __('message.success'));
    }
}
