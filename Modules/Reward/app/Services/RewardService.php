<?php

namespace Modules\Reward\Services;

use App\Traits\FileUploadTrait;
use Modules\Reward\Repositories\RewardRepository;
use Modules\Reward\Models\Reward;
use Illuminate\Support\Facades\DB;
use Modules\LoyaltySetting\Models\LoyaltyTransaction;
use App\Enums\LoyaltyTrransactionTypeEnum;
use App\Enums\RewardType;

use App\Enums\OrderStatus;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;

class RewardService
{
    use FileUploadTrait;

    public function __construct(
        protected RewardRepository $rewardRepository
    ) {}

    public function getAllRewards($filters = [])
    {
        return $this->rewardRepository->paginate(
            $filters,
            15,
            ['country', 'coupon']
        );
    }

    public function createReward(array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->upload(
                request(),
                'image',
                'uploads/rewards',
                'public'
            );
        }

        return $this->rewardRepository->create($data);
    }

    public function getRewardById(int $id): ?Reward
    {
        return $this->rewardRepository->find($id, ['country', 'coupon', 'redemptions']);
    }

    public function updateReward(int $id, array $data): ?Reward
    {
        if (isset($data['image'])) {
            $data['image'] = $this->upload(
                request(),
                'image',
                'uploads/rewards',
                'public'
            );
        }

        return $this->rewardRepository->update($id, $data);
    }

    public function deleteReward(int $id): bool
    {
        return $this->rewardRepository->delete($id);
    }

    public function getAvailableRewards(array $filters = [])
    {
        return $this->rewardRepository->getAvailableRewards(
            $filters,
            ['country', 'coupon']
        );
    }

    public function getUserRedemptions(int $userId)
    {
        return $this->rewardRepository->getUserRedemptions(
            $userId,
            ['reward', 'reward.country']
        );
    }

    /**
     * Redeem a reward for a user
     */
    public function redeemReward(int $rewardId, int $userId)
    {
        return DB::transaction(function () use ($rewardId, $userId) {
            $reward = $this->rewardRepository->find($rewardId, ['coupon']);
            $user = \Modules\User\Models\User::findOrFail($userId);

            // Validate reward can be redeemed
            $this->validateRedemption($reward, $user);

            // Deduct points from user
            $user->decrement('loyalty_points', $reward->points_cost);

            // Create loyalty transaction for points deduction
            LoyaltyTransaction::create([
                'user_id' => $userId,
                'type' => LoyaltyTrransactionTypeEnum::REDEEMED,
                'points' => -$reward->points_cost,
                'points_balance_after' => $user->fresh()->loyalty_points,
                'description' => "Redeemed reward: {$reward->title}",
                'referenceable_type' => Reward::class,
                'referenceable_id' => $rewardId,
            ]);

            // Process reward based on type
            $couponCode = null;
            if ($reward->type === RewardType::WALLET) {
                // Add to wallet balance
                $user->wallet()->firstOrCreate([])->increment('balance', $reward->value_amount);
            } elseif ($reward->type === RewardType::COUPON && $reward->coupon) {
                // Assign coupon to user
                $couponCode = $reward->coupon->code;
                // You may want to create a user_coupon relationship here
            }

            // Create redemption record
            $redemption = $this->rewardRepository->createRedemption([
                'user_id' => $userId,
                'reward_id' => $rewardId,
                'points_spent' => $reward->points_cost,
                'reward_value_received' => $reward->value_amount,
                'coupon_code' => $couponCode,
                'status' => 'completed',
                'redeemed_at' => now(),
            ]);

            // Increment total redemptions
            $this->rewardRepository->incrementTotalRedemptions($rewardId);

            return $redemption->load('reward');
        });
    }

    /**
     * Validate if user can redeem the reward
     */
    protected function validateRedemption(Reward $reward, $user)
    {
        // Check if reward is active
        if (!$reward->isActive()) {
            throw new \Exception('This reward is not currently available.');
        }

        // Check if reward has reached usage limit
        if ($reward->hasReachedLimit()) {
            throw new \Exception('This reward has reached its redemption limit.');
        }

        // Check if user has enough points
        if ($user->loyalty_points < $reward->points_cost) {
            throw new \Exception('You do not have enough points to redeem this reward.');
        }

        // Check if user has exceeded their personal redemption limit
        if (!$reward->canUserRedeem($user->id)) {
            throw new \Exception('You have already redeemed this reward the maximum number of times.');
        }

        // For coupon rewards, validate coupon exists
        if ($reward->type === RewardType::COUPON && !$reward->coupon) {
            throw new \Exception('This reward\'s coupon is not available.');
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        // Top 3 users by loyalty points
        $topPointsUsers = User::orderByDesc('loyalty_points')
            ->take(3)
            ->get();

        // Top 3 users by order total amount (completed/delivered orders)
        $topSpenders = User::withSum(['orders' => function ($query) {
            $query->where('status', OrderStatus::DELIVERED);
        }], 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->take(3)
            ->get();

        // Active rewards filtered by user's country
        $filters = [
            'type' => RewardType::PRIZE,
        ];

        // Add user's country filter if available
        $user = Auth::user();
        if ($user && $user->country_id) {
            $filters['country_id'] = $user->country_id;
        }

        // Get loyalty reward
        $loyaltyFilters = $filters;
        $loyaltyFilters['is_it_for_loyalty_points'] = true;
        $loyaltyReward = $this->getAvailableRewards($loyaltyFilters)->first();

        // Get spending reward
        $spendingFilters = $filters;
        $spendingFilters['is_it_for_spendings'] = true;
        $spendingReward = $this->getAvailableRewards($spendingFilters)->first();

        return [
            'top_points_users' => $topPointsUsers,
            'top_spenders' => $topSpenders,
            'loyalty_reward' => $loyaltyReward,
            'spending_reward' => $spendingReward,
        ];
    }
}
