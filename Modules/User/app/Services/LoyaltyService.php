<?php

namespace Modules\User\Services;

use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use Modules\User\Models\User;
use Illuminate\Support\Collection;

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
            'available_points' => $user->getAvailablePoints(),
            'total_points' => $user->loyalty_points,
            'points_expire_at' => $user->points_expire_at,
            'settings' => [
                'is_enabled' => $settings->is_enabled,
                'points_per_currency' => $settings->points_per_currency,
                'redemption_rate' => $settings->redemption_rate,
                'minimum_redemption_points' => $settings->minimum_redemption_points,
                'maximum_redemption_percentage' => $settings->maximum_redemption_percentage,
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

        if (!$settings->isEnabled()) {
            return false;
        }

        $points = $settings->calculatePoints($amount);

        return $user->awardPoints($points, $description, $referenceable);
    }

    /**
     * Calculate redemption value for points
     */
    public function calculateRedemptionValue(float $points): float
    {
        $settings = $this->getSettings();
        return $settings->calculateRedemptionValue($points);
    }

    /**
     * Validate points redemption
     */
    public function validateRedemption(int $userId, float $points, float $orderTotal): array
    {
        $user = User::findOrFail($userId);
        $settings = $this->getSettings();

        $errors = [];

        // Check if loyalty is enabled
        if (!$settings->isEnabled()) {
            $errors[] = 'Loyalty program is not available';
        }

        // Check minimum points requirement
        if ($points < $settings->minimum_redemption_points) {
            $errors[] = "Minimum redemption is {$settings->minimum_redemption_points} points";
        }

        // Check user has enough points
        if (!$user->hasEnoughPoints($points)) {
            $errors[] = 'Insufficient loyalty points';
        }

        // Check maximum redemption percentage
        $redemptionValue = $this->calculateRedemptionValue($points);
        $maxAllowed = $orderTotal * ($settings->maximum_redemption_percentage / 100);

        if ($redemptionValue > $maxAllowed) {
            $errors[] = "Maximum redemption is {$settings->maximum_redemption_percentage}% of order total";
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
}
