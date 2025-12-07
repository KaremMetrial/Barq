<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\OrderStatus;
use Modules\User\Models\User;
use Modules\Order\Models\Order;
use Modules\Review\Models\Review;
use Modules\LoyaltySetting\Models\LoyaltySetting;
use Modules\LoyaltySetting\Models\LoyaltyTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoyaltyRewardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // LoyaltySetting::getSettings() will create default settings if none exist
        // This avoids the need to create Country with all required fields
    }

    /**
     * Test that referrer receives points when referred user completes their first order
     */
    public function test_referrer_receives_points_when_referred_user_completes_first_order()
    {
        // Create referrer user
        $referrer = User::factory()->create([
            'loyalty_points' => 0,
        ]);

        // Create referred user
        $referredUser = User::factory()->create([
            'referral_id' => $referrer->id,
            'loyalty_points' => 0,
        ]);

        // Create an order for the referred user
        $order = Order::factory()->create([
            'user_id' => $referredUser->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 50.00,
        ]);

        // Mark order as delivered
        $order->update(['status' => OrderStatus::DELIVERED]);

        // Refresh referrer to get updated points
        $referrer->refresh();

        // Assert referrer received 200 points
        $this->assertEquals(200, $referrer->loyalty_points);

        // Assert transaction was created
        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $referrer->id,
            'type' => 'earned',
            'points' => 200,
        ]);
    }

    /**
     * Test that referrer only gets points for the first completed order
     */
    public function test_referrer_only_gets_points_for_first_order()
    {
        // Create referrer user
        $referrer = User::factory()->create([
            'loyalty_points' => 0,
        ]);

        // Create referred user
        $referredUser = User::factory()->create([
            'referral_id' => $referrer->id,
            'loyalty_points' => 0,
        ]);

        // Create and complete first order
        $order1 = Order::factory()->create([
            'user_id' => $referredUser->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 50.00,
        ]);
        $order1->update(['status' => OrderStatus::DELIVERED]);

        // Create and complete second order
        $order2 = Order::factory()->create([
            'user_id' => $referredUser->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 50.00,
        ]);
        $order2->update(['status' => OrderStatus::DELIVERED]);

        // Refresh referrer
        $referrer->refresh();

        // Assert referrer only received 200 points (not 400)
        $this->assertEquals(200, $referrer->loyalty_points);

        // Assert only one transaction was created
        $this->assertEquals(1, LoyaltyTransaction::where('user_id', $referrer->id)->count());
    }

    /**
     * Test that user receives points when rating an order
     */
    public function test_user_receives_points_when_rating_order()
    {
        // Create user
        $user = User::factory()->create([
            'loyalty_points' => 0,
        ]);

        // Create an order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::DELIVERED,
            'total_amount' => 50.00,
        ]);

        // Create a review for the order
        $review = Review::create([
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Great service!',
            'reviewable_id' => $order->store_id,
            'reviewable_type' => 'Modules\\Store\\Models\\Store',
        ]);

        // Refresh user
        $user->refresh();

        // Assert user received 30 points
        $this->assertEquals(30, $user->loyalty_points);

        // Assert transaction was created
        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 30,
        ]);
    }

    /**
     * Test that user doesn't receive duplicate points for the same review
     */
    public function test_user_does_not_receive_duplicate_points_for_same_review()
    {
        // Create user
        $user = User::factory()->create([
            'loyalty_points' => 0,
        ]);

        // Create an order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::DELIVERED,
            'total_amount' => 50.00,
        ]);

        // Create a review
        $review = Review::create([
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Great service!',
            'reviewable_id' => $order->store_id,
            'reviewable_type' => 'Modules\\Store\\Models\\Store',
        ]);

        // Try to award points again manually
        $loyaltyService = app(\Modules\User\Services\LoyaltyService::class);
        $result = $loyaltyService->awardRatingPoints($user->id, $review);

        // Assert that points were not awarded again
        $this->assertFalse($result);

        // Refresh user
        $user->refresh();

        // Assert user still has only 30 points
        $this->assertEquals(30, $user->loyalty_points);
    }
}
