<?php

namespace Modules\Couier\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Couier\Models\Couier;
use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Couier\Models\CouierShift;
use Modules\Couier\Services\SmartOrderAssignmentService;
use Modules\Couier\Services\RealTimeCourierService;
use Modules\Couier\Services\GeographicCourierService;
use App\Enums\UserStatusEnum;
use App\Enums\CouierAvaliableStatusEnum;
use Carbon\Carbon;
use Mockery;

class SmartOrderAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    // private SmartOrderAssignmentService $assignmentService;
    private $realTimeMock;
    private $geographicMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the dependencies
        $this->realTimeMock = Mockery::mock(RealTimeCourierService::class);
        $this->geographicMock = Mockery::mock(GeographicCourierService::class);

        // Replace the bindings in the service container
        $this->app->bind(RealTimeCourierService::class, function () {
            return $this->realTimeMock;
        });

        $this->app->bind(GeographicCourierService::class, function () {
            return $this->geographicMock;
        });

        // $this->assignmentService = app(SmartOrderAssignmentService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_auto_assign_order_success()
    {
        // Create test courier with active shift
        $courier = Couier::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'avaliable_status' => CouierAvaliableStatusEnum::AVAILABLE->value,
        ]);

        // Create active shift for courier
        CouierShift::create([
            'couier_id' => $courier->id,
            'start_time' => now()->subMinutes(30),
            'is_open' => true,
        ]);

        $orderData = [
            'order_id' => 123,
            'pickup_lat' => 30.0444,
            'pickup_lng' => 31.2357,
            'delivery_lat' => 30.0500,
            'delivery_lng' => 31.2500,
            'priority_level' => 'normal',
            'courier_shift_id' => null,
        ];

        // Mock geographic service to return our courier
        $this->geographicMock
            ->shouldReceive('findOptimalCouriers')
            ->once()
            ->with($orderData['pickup_lat'], $orderData['pickup_lng'], null, null, [
                'priority' => 'balanced',
                'max_results' => 5,
                'radius_km' => 5.0,
            ])
            ->andReturn(collect([$courier]));

        // Mock real-time service
        $this->realTimeMock
            ->shouldReceive('notifyOrderAssigned')
            ->once()
            ->with($courier->id, Mockery::type(CourierOrderAssignment::class));

        // Execute
        $assignment = $this->assignmentService->autoAssignOrder($orderData, 120);

        // Assert
        $this->assertInstanceOf(CourierOrderAssignment::class, $assignment);
        $this->assertEquals($courier->id, $assignment->courier_id);
        $this->assertEquals('assigned', $assignment->status);
        $this->assertNotNull($assignment->expires_at);
    }

    public function test_auto_assign_order_no_couriers_found()
    {
        $orderData = [
            'order_id' => 123,
            'pickup_lat' => 30.0444,
            'pickup_lng' => 31.2357,
        ];

        // Mock geographic service to return empty collection
        $this->geographicMock
            ->shouldReceive('findOptimalCouriers')
            ->once()
            ->andReturn(collect([]));

        // Execute and assert
        $assignment = $this->assignmentService->autoAssignOrder($orderData, 120);
        $this->assertNull($assignment);
    }

    public function test_accept_assignment_success()
    {
        // Create assignment
        $assignment = CourierOrderAssignment::factory()->create([
            'status' => 'assigned',
            'expires_at' => now()->addMinutes(5),
        ]);

        // Create courier with active shift
        $courier = Couier::factory()->create();
        CouierShift::create([
            'couier_id' => $courier->id,
            'start_time' => now()->subMinutes(30),
            'is_open' => true,
        ]);

        // Update assignment to belong to this courier
        $assignment->update(['courier_id' => $courier->id]);

        // Mock real-time service
        $this->realTimeMock
            ->shouldReceive('notifyOrderStatusChanged')
            ->twice(); // Once for accepted, once for finalized

        // Execute
        $result = $this->assignmentService->acceptAssignment($assignment->id, $courier->id);

        // Assert
        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals('accepted', $assignment->status);
        $this->assertNotNull($assignment->accepted_at);
    }

    public function test_accept_assignment_expired_fails()
    {
        // Create expired assignment
        $assignment = CourierOrderAssignment::factory()->create([
            'status' => 'assigned',
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        $courier = Couier::factory()->create();

        // Execute and assert
        $result = $this->assignmentService->acceptAssignment($assignment->id, $courier->id);
        $this->assertFalse($result);
    }

    public function test_reject_assignment_success()
    {
        // Create assignment
        $assignment = CourierOrderAssignment::factory()->create([
            'status' => 'assigned',
        ]);

        $courier = Couier::factory()->create();
        $assignment->update(['courier_id' => $courier->id]);

        // Mock that reassignment is possible (single assignment for order)
        // Create another courier for potential reassignment
        $anotherCourier = Couier::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'avaliable_status' => CouierAvaliableStatusEnum::AVAILABLE->value,
        ]);

        CouierShift::create([
            'couier_id' => $anotherCourier->id,
            'start_time' => now()->subMinutes(30),
            'is_open' => true,
        ]);

        // Mock geographic service for reassignment
        $this->geographicMock
            ->shouldReceive('findOptimalCouriers')
            ->once()
            ->andReturn(collect([$anotherCourier]));

        // Mock real-time service for reassignment notification
        $this->realTimeMock
            ->shouldReceive('notifyOrderStatusChanged')
            ->once()
            ->with($assignment->order_id, 'assignment_finalized', ['accepted_courier_id' => $courier->id]);

        $this->realTimeMock
            ->shouldReceive('notifyOrderAssigned')
            ->once();

        // Execute
        $result = $this->assignmentService->rejectAssignment($assignment->id, $courier->id, 'Busy right now');

        // Assert
        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals('rejected', $assignment->status);
        $this->assertEquals('Busy right now', $assignment->rejection_reason);
    }

    public function test_update_assignment_status_success()
    {
        // Create accepted assignment
        $assignment = CourierOrderAssignment::factory()->create([
            'status' => 'accepted',
        ]);

        $courier = Couier::factory()->create();
        $assignment->update(['courier_id' => $courier->id]);

        // Mock real-time service
        $this->realTimeMock
            ->shouldReceive('notifyOrderStatusChanged')
            ->once()
            ->with($assignment->order_id, 'in_transit', [
                'courier_id' => $courier->id,
                'assignment_id' => $assignment->id
            ]);

        // Execute start delivery
        $result = $this->assignmentService->updateAssignmentStatus($assignment->id, 'in_transit');

        // Assert
        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals('in_transit', $assignment->status);
        $this->assertNotNull($assignment->started_at);
    }

    public function test_update_assignment_status_delivered()
    {
        // Create in-transit assignment
        $assignment = CourierOrderAssignment::factory()->create([
            'status' => 'in_transit',
            'started_at' => now()->subMinutes(30),
        ]);

        $courier = Couier::factory()->create();
        $assignment->update(['courier_id' => $courier->id]);

        // Mock real-time service
        $this->realTimeMock
            ->shouldReceive('notifyOrderStatusChanged')
            ->once();

        // Execute delivery completion
        $result = $this->assignmentService->updateAssignmentStatus($assignment->id, 'delivered');

        // Assert
        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals('delivered', $assignment->status);
        $this->assertNotNull($assignment->completed_at);
        $this->assertNotNull($assignment->actual_duration_minutes);
    }

    public function test_handle_timeout_success()
    {
        // Create assignment
        $assignment = CourierOrderAssignment::factory()->create([
            'status' => 'assigned',
        ]);

        $courier = Couier::factory()->create();
        $assignment->update(['courier_id' => $courier->id]);

        // Create another courier for reassignment
        $anotherCourier = Couier::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'avaliable_status' => CouierAvaliableStatusEnum::AVAILABLE->value,
        ]);

        CouierShift::create([
            'couier_id' => $anotherCourier->id,
            'start_time' => now()->subMinutes(30),
            'is_open' => true,
        ]);

        // Mock services
        $this->geographicMock
            ->shouldReceive('findOptimalCouriers')
            ->once()
            ->andReturn(collect([$anotherCourier]));

        $this->realTimeMock
            ->shouldReceive('notifyOrderExpired')
            ->once()
            ->with($courier->id, $assignment->order_id, 'timeout');

        $this->realTimeMock
            ->shouldReceive('notifyOrderAssigned')
            ->once();

        // Execute timeout handling
        $this->assignmentService->handleTimeout($assignment->id);

        // Assert original assignment is marked as timed out
        $assignment->refresh();
        $this->assertEquals('timed_out', $assignment->status);

        // Assert a new assignment was created
        $newAssignment = CourierOrderAssignment::where('order_id', $assignment->order_id)
            ->where('status', 'assigned')
            ->where('courier_id', $anotherCourier->id)
            ->first();

        $this->assertNotNull($newAssignment);
    }

    public function test_get_courier_active_assignments()
    {
        $courier = Couier::factory()->create();

        // Create various assignments
        CourierOrderAssignment::factory()->create([
            'courier_id' => $courier->id,
            'status' => 'assigned',
        ]);

        CourierOrderAssignment::factory()->create([
            'courier_id' => $courier->id,
            'status' => 'accepted',
        ]);

        CourierOrderAssignment::factory()->create([
            'courier_id' => $courier->id,
            'status' => 'in_transit',
        ]);

        CourierOrderAssignment::factory()->create([
            'courier_id' => $courier->id,
            'status' => 'delivered', // Should not be included
        ]);

        // Execute
        $activeAssignments = $this->assignmentService->getCourierActiveAssignments($courier->id);

        // Assert
        $this->assertCount(3, $activeAssignments);
        $this->assertEquals(['assigned', 'accepted', 'in_transit'], $activeAssignments->pluck('status')->sort()->values()->toArray());
    }
}
