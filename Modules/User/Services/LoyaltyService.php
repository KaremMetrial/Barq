<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    /**
     * Award loyalty points to user after order completion
     */
    public function awardPoints(int $userId, float $orderAmount, string $reason, Order $order): void
    {
        DB::transaction(function () use ($userId, $orderAmount, $reason, $order) {
            $user = User::find($userId);
            if (!$user) {
                return;
            }

            // Calculate points based on order amount
            $points = $this->calculatePointsFromOrder($orderAmount, $order);

            if ($points <= 0) {
                return;
            }

            // Update user points
            $user->increment('loyalty_points', $points);

            // Record the transaction
            $this->recordTransaction($userId, $points, $reason, 'earned', $order->id);
        });
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(int $userId, int $points, string $reason = null): bool
    {
        $user = User::find($userId);
        if (!$user || $user->loyalty_points < $points) {
            return false;
        }

        DB::transaction(function () use ($user, $points, $reason) {
            // Deduct points
            $user->decrement('loyalty_points', $points);

            // Record the transaction
            $this->recordTransaction($user->id, $points, $reason ?? 'Points redeemed', 'redeemed');
        });

        return true;
    }

    /**
     * Get user loyalty balance
     */
    public function getBalance(int $userId): int
    {
        $user = User::find($userId);
        return $user ? $user->loyalty_points : 0;
    }

    /**
     * Convert points to currency value
     */
    public function pointsToValue(int $points): float
    {
        $rate = config('loyalty.points_to_currency_rate', 1); // 1 point = 1 currency unit by default
        return $points / $rate;
    }

    /**
     * Convert currency value to points
     */
    public function valueToPoints(float $value): int
    {
        $rate = config('loyalty.currency_to_points_rate', 1); // 1 currency unit = 1 point by default
        return (int) ($value * $rate);
    }

    /**
     * Calculate points from order amount
     */
    private function calculatePointsFromOrder(float $amount, Order $order): int
    {
        // Points per order amount (e.g., 1 point per 10 currency units)
        $pointsPerUnit = config('loyalty.points_per_order_unit', 0.1); // Default: 1 point per 10 units
        $points = (int) ($amount * $pointsPerUnit);

        // Bonus points for delivery orders
        if ($order->isDeliver()) {
            $deliveryBonus = config('loyalty.delivery_bonus_points', 2);
            $points += $deliveryBonus;
        }

        // Tiered bonus based on order amount
        $tierSettings = config('loyalty.tiered_bonus', []);
        foreach ($tierSettings as $tier) {
            if ($amount >= $tier['min_amount']) {
                $points += $tier['bonus_points'];
            }
        }

        return max(0, $points);
    }

    /**
     * Record Loyalty Transaction - simplified logging approach
     */
    private function recordTransaction(int $userId, int $points, string $reason, string $type = 'earned', ?int $orderId = null): void
    {
        // Log the transaction for now - you can create a proper LoyaltyTransaction model later
        Log::info('Loyalty transaction recorded', [
            'user_id' => $userId,
            'points' => $points,
            'type' => $type,
            'reason' => $reason,
            'order_id' => $orderId,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get user transaction history
     */
    public function getTransactionHistory(int $userId, int $limit = 20): \Illuminate\Support\Collection
    {
        // For now, return empty collection since we're using logging
        // When you create LoyaltyTransaction model, you can implement proper history
        Log::info('LoyaltyService: getTransactionHistory called', [
            'user_id' => $userId,
            'limit' => $limit
        ]);

        return collect();
    }
}
