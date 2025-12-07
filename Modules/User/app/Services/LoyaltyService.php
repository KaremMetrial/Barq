<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use Illuminate\Support\Collection;
use Modules\LoyaltySetting\Models\LoyaltySetting;
use Modules\LoyaltySetting\Models\LoyaltyTransaction;
use Modules\Order\Models\Order;
use Modules\Review\Models\Review;

class LoyaltyService
{
    /**
     * Get loyalty settings
     */
    public function getSettings(): LoyaltySetting
    {
        return LoyaltySetting::getSettings();
    }

    /**
     * Update loyalty settings
     */
    public function updateSettings(array $data): LoyaltySetting
    {
        $settings = LoyaltySetting::first();

        if (!$settings) {
            $settings = new LoyaltySetting();
        }

        $settings->fill($data);
        $settings->save();

        return $settings;
    }

    /**
     * Get user loyalty balance and info
     */
    public function getUserLoyaltyInfo(int $userId): array
    {
        $user = User::findOrFail($userId);
        $settings = $this->getSettings();

        return [
            'total_points' => $user->loyalty_points,
            'settings' => [
                'earn_rate' => $settings->earn_rate,
                'min_order_for_earn' => $settings->min_order_for_earn,
                'referral_points' => $settings->referral_points,
                'rating_points' => $settings->rating_points,
            ],
        ];
    }

    /**
     * Get user loyalty transaction history
     */
    public function getUserTransactionHistory(int $userId, int $limit = 20): Collection
    {
        return LoyaltyTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Award points to user (typically called after order completion)
     */
    public function awardPoints(int $userId, float $amount, string $description = null, $referenceable = null): bool
    {
        $user = User::findOrFail($userId);
        $settings = $this->getSettings();

        // Check if amount meets minimum order requirement
        if ($amount < $settings->min_order_for_earn) {
            return false;
        }

        $points = $settings->calculatePoints($amount);

        return $user->awardPoints($points, $description, $referenceable);
    }

    /**
     * Calculate redemption value for points
     * Note: This is a simplified calculation. Adjust based on your business logic.
     */
    public function calculateRedemptionValue(float $points): float
    {
        $settings = $this->getSettings();
        // Convert points to currency value using earn_rate
        // If earn_rate is 0.10 (10%), then 100 points = $10
        return $points / ($settings->earn_rate * 10);
    }

    /**
     * Validate points redemption
     */
    public function validateRedemption(int $userId, float $points, float $orderTotal): array
    {
        $user = User::findOrFail($userId);
        $settings = $this->getSettings();

        $errors = [];

        // Check minimum points requirement (use 100 as default minimum)
        $minimumPoints = 100;
        if ($points < $minimumPoints) {
            $errors[] = "Minimum redemption is {$minimumPoints} points";
        }

        // Check user has enough points
        if ($user->loyalty_points < $points) {
            $errors[] = 'Insufficient loyalty points';
        }

        // Check maximum redemption percentage (50% of order total as default)
        $redemptionValue = $this->calculateRedemptionValue($points);
        $maxPercentage = 50; // 50% max
        $maxAllowed = $orderTotal * ($maxPercentage / 100);

        if ($redemptionValue > $maxAllowed) {
            $errors[] = "Maximum redemption is {$maxPercentage}% of order total";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'redemption_value' => $redemptionValue,
            'max_allowed' => $maxAllowed,
        ];
    }

    /**
     * Redeem points for user
     */
    public function redeemPoints(int $userId, float $points, string $description = null, $referenceable = null): bool
    {
        $user = User::findOrFail($userId);
        return $user->redeemPoints($points, $description, $referenceable);
    }

    /**
     * Expire old points (should be called by scheduled job)
     */
    public function expireOldPoints(): int
    {
        $expiredTransactions = LoyaltyTransaction::active()
            ->where('expires_at', '<=', now())
            ->get();

        $expiredCount = 0;

        foreach ($expiredTransactions as $transaction) {
            // Create expiration transaction
            LoyaltyTransaction::create([
                'user_id' => $transaction->user_id,
                'type' => 'expired',
                'points' => -$transaction->points, // Negative to show deduction
                'points_balance_after' => max(0, $transaction->user->loyalty_points - $transaction->points),
                'description' => 'Points expired',
                'referenceable_type' => get_class($transaction),
                'referenceable_id' => $transaction->id,
            ]);

            // Update user balance
            $transaction->user->decrement('loyalty_points', $transaction->points);
            $expiredCount++;
        }

        return $expiredCount;
    }

    /**
     * Award referral points to referrer when referred user completes their first order
     */
    public function awardReferralPoints(int $referrerId, int $referredUserId, Order $order): bool
    {
        $referrer = User::find($referrerId);
        if (!$referrer) {
            return false;
        }

        // Check if this is the referred user's first completed order
        $completedOrdersCount = Order::where('user_id', $referredUserId)
            ->where('status', \App\Enums\OrderStatus::DELIVERED)
            ->count();

        // Only award points for the first completed order
        if ($completedOrdersCount > 1) {
            return false;
        }

        $settings = $this->getSettings();
        $points = $settings->referral_points;

        return $referrer->awardPoints(
            $points,
            "Referral bonus for user #{$referredUserId}",
            $order
        );
    }

    /**
     * Award rating points to user for rating an order
     */
    public function awardRatingPoints(int $userId, Review $review): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Check if user already received points for this review
        $existingTransaction = LoyaltyTransaction::where('user_id', $userId)
            ->where('referenceable_type', get_class($review))
            ->where('referenceable_id', $review->id)
            ->exists();

        if ($existingTransaction) {
            return false;
        }

        $settings = $this->getSettings();
        $points = $settings->rating_points;

        return $user->awardPoints(
            $points,
            "Rating bonus for order #{$review->order_id}",
            $review
        );
    }
}
