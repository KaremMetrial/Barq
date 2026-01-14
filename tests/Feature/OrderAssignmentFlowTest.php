<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Modules\Couier\Models\Couier;
use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Order\Models\Order;
use Modules\Zone\Models\Zone;
use App\Jobs\CourierAssignmentTimeoutJob;
use Modules\Couier\Services\SmartOrderAssignmentService;
use Illuminate\Support\Facades\Notification;

class OrderAssignmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $assignmentService;
    protected $courier;
    protected $order;
    protected $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignmentService = $this->app->make(SmartOrderAssignmentService::class);

        // Create test data
        $this->zone = Zone::factory()->create();
        $this->courier = Couier::factory()->create();
        $this->order = Order::factory()->create([
            'store_id' => 1,
            'status' => 'ready_for_delivery'
        ]);

        // Assign courier to zone
        $this->courier->zonesToCover()->attach($this->zone->id);
    }

    /** @test */
    public function it_creates_order_assignment_with_correct_timeout()
    {
        // Test data
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        // Create assignment
        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Assertions
        $this->assertNotNull($assignment);
        $this->assertEquals('assigned', $assignment->status);
        $this->assertEquals($this->courier->id, $assignment->courier_id);
        $this->assertEquals($this->order->id, $assignment->order_id);
        $this->assertNotNull($assignment->expires_at);

        // Check timeout is set to 120 seconds (2 minutes)
        $timeRemaining = $assignment->time_remaining;
        $this->assertEquals(120, $timeRemaining);
        $this->assertTrue($assignment->expires_at->gt(now()));

        // Verify assignment is not expired yet
        $this->assertFalse($assignment->is_expired);
    }

    /** @test */
    public function it_handles_assignment_timeout_correctly()
    {
        // Create assignment
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);
        $assignmentId = $assignment->id;

        // Fast-forward time to trigger timeout (121 seconds)
        $this->travel(121)->seconds();

        // Manually trigger the timeout job
        $timeoutJob = new CourierAssignmentTimeoutJob($assignmentId);
        $timeoutJob->handle($this->assignmentService);

        // Refresh assignment
        $assignment->refresh();

        // Assertions
        $this->assertEquals('timed_out', $assignment->status);
        $this->assertTrue($assignment->is_expired);
        $this->assertLessThanOrEqual(0, $assignment->time_remaining);

        // Verify the assignment is marked as expired
        $this->assertTrue($assignment->expires_at->lt(now()));
    }

    /** @test */
    public function it_allows_courier_to_accept_assignment_before_timeout()
    {
        // Create assignment
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Courier accepts the assignment
        $result = $this->assignmentService->acceptAssignment($assignment->id, $this->courier->id);

        // Assertions
        $this->assertTrue($result);

        // Refresh assignment
        $assignment->refresh();

        $this->assertEquals('accepted', $assignment->status);
        $this->assertNotNull($assignment->accepted_at);
        $this->assertFalse($assignment->is_expired);
    }

    /** @test */
    public function it_prevents_accepting_expired_assignment()
    {
        // Create assignment
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Fast-forward time to expire the assignment
        $this->travel(121)->seconds();

        // Try to accept expired assignment
        $result = $this->assignmentService->acceptAssignment($assignment->id, $this->courier->id);

        // Assertions
        $this->assertFalse($result);

        // Verify assignment status is still timed_out
        $assignment->refresh();
        $this->assertEquals('timed_out', $assignment->status);
    }

    /** @test */
    public function it_allows_courier_to_reject_assignment()
    {
        // Create assignment
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Courier rejects the assignment
        $reason = 'Too far from current location';
        $result = $this->assignmentService->rejectAssignment($assignment->id, $this->courier->id, $reason);

        // Assertions
        $this->assertTrue($result);

        // Refresh assignment
        $assignment->refresh();

        $this->assertEquals('rejected', $assignment->status);
        $this->assertEquals($reason, $assignment->rejection_reason);
        $this->assertNotNull($assignment->completed_at);
    }

    /** @test */
    public function it_schedules_timeout_job_when_assignment_is_created()
    {
        // Fake the job bus to catch scheduled jobs
        Bus::fake();

        // Create assignment
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Assert that timeout job was scheduled
        Bus::assertDispatched(CourierAssignmentTimeoutJob::class, function ($job) use ($assignment) {
            return $job->assignmentId === $assignment->id;
        });
    }

    /** @test */
    public function it_handles_multiple_couriers_and_reassignment()
    {
        // Create second courier
        $courier2 = Couier::factory()->create();
        $courier2->zonesToCover()->attach($this->zone->id);

        // Create first assignment to courier1
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment1 = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Let assignment timeout
        $this->travel(121)->seconds();
        $timeoutJob = new CourierAssignmentTimeoutJob($assignment1->id);
        $timeoutJob->handle($this->assignmentService);

        // Verify first assignment is timed out
        $assignment1->refresh();
        $this->assertEquals('timed_out', $assignment1->status);

        // Create new order for reassignment test
        $order2 = Order::factory()->create([
            'store_id' => 1,
            'status' => 'ready_for_delivery'
        ]);

        $orderData2 = [
            'order_id' => $order2->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        // Create new assignment (should go to courier2 since courier1 had timeout)
        $assignment2 = $this->assignmentService->assignOrderToNearestCourier($orderData2);

        // Verify new assignment is created
        $this->assertNotNull($assignment2);
        $this->assertEquals('assigned', $assignment2->status);
        $this->assertContains($assignment2->courier_id, [$this->courier->id, $courier2->id]);
    }

    /** @test */
    public function it_correctly_calculates_time_remaining()
    {
        // Create assignment
        $orderData = [
            'order_id' => $this->order->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'delivery_lat' => 25.2148,
            'delivery_lng' => 55.2808,
            'priority_level' => 'normal',
            'zone_id' => $this->zone->id
        ];

        $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData);

        // Test time remaining calculations
        $this->assertEquals(120, $assignment->time_remaining);

        // Fast-forward 30 seconds
        $this->travel(30)->seconds();
        $assignment->refresh();
        $this->assertEquals(90, $assignment->time_remaining);

        // Fast-forward another 30 seconds
        $this->travel(30)->seconds();
        $assignment->refresh();
        $this->assertEquals(60, $assignment->time_remaining);

        // Fast-forward to expiration
        $this->travel(61)->seconds();
        $assignment->refresh();
        $this->assertEquals(0, $assignment->time_remaining);
        $this->assertTrue($assignment->is_expired);
    }
}
