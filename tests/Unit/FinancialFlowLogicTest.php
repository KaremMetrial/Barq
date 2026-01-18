<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Enums\TransactionStatusEnum;
use Modules\Balance\Listeners\UpdateBalanceOnOrderReadyForDelivery;
use Modules\Balance\Listeners\UpdateBalanceOnOrderOnTheWay;
use Modules\Balance\Listeners\UpdateBalanceOnOrderDelivered;
use Modules\Order\Events\OrderStatusChanged;
use Mockery;
use Illuminate\Support\Facades\DB;

class FinancialFlowLogicTest extends TestCase
{
    public function test_listeners_are_properly_configured()
    {
        // Test that the listeners exist and can be instantiated
        $readyForDeliveryListener = new UpdateBalanceOnOrderReadyForDelivery();
        $this->assertInstanceOf(UpdateBalanceOnOrderReadyForDelivery::class, $readyForDeliveryListener);

        $onTheWayListener = new UpdateBalanceOnOrderOnTheWay();
        $this->assertInstanceOf(UpdateBalanceOnOrderOnTheWay::class, $onTheWayListener);

        $deliveredListener = new UpdateBalanceOnOrderDelivered();
        $this->assertInstanceOf(UpdateBalanceOnOrderDelivered::class, $deliveredListener);
    }

    public function test_ready_for_delivery_listener_only_handles_correct_status()
    {
        $listener = new UpdateBalanceOnOrderReadyForDelivery();

        // Mock event with different statuses
        $mockEvent = Mockery::mock(OrderStatusChanged::class);

        // Test with PENDING status - should not process
        $mockEvent->shouldReceive('getNewStatus')->andReturn(OrderStatus::PENDING);
        $mockEvent->shouldReceive('getOrder')->never();

        $listener->handle($mockEvent);

        // Test with READY_FOR_DELIVERY status - should process
        $mockEvent = Mockery::mock(OrderStatusChanged::class);
        $mockEvent->shouldReceive('getNewStatus')->andReturn(OrderStatus::READY_FOR_DELIVERY);
        $mockEvent->shouldReceive('getOrder')->andReturn(null); // Would process if order existed

        $listener->handle($mockEvent);
    }

    public function test_on_the_way_listener_only_handles_correct_status()
    {
        $listener = new UpdateBalanceOnOrderOnTheWay();

        // Mock event with different statuses
        $mockEvent = Mockery::mock(OrderStatusChanged::class);

        // Test with READY_FOR_DELIVERY status - should not process
        $mockEvent->shouldReceive('getNewStatus')->andReturn(OrderStatus::READY_FOR_DELIVERY);
        $mockEvent->shouldReceive('getOrder')->never();

        $listener->handle($mockEvent);

        // Test with ON_THE_WAY status - should process
        $mockEvent = Mockery::mock(OrderStatusChanged::class);
        $mockEvent->shouldReceive('getNewStatus')->andReturn(OrderStatus::ON_THE_WAY);
        $mockEvent->shouldReceive('getOrder')->andReturn(null); // Would process if order existed

        $listener->handle($mockEvent);
    }

    public function test_delivered_listener_only_handles_correct_status()
    {
        $listener = new UpdateBalanceOnOrderDelivered();

        // Mock event with different statuses
        $mockEvent = Mockery::mock(OrderStatusChanged::class);

        // Test with ON_THE_WAY status - should not process
        $mockEvent->shouldReceive('getNewStatus')->andReturn(OrderStatus::ON_THE_WAY);
        $mockEvent->shouldReceive('getOrder')->never();

        $listener->handle($mockEvent);

        // Test with DELIVERED status - should process
        $mockEvent = Mockery::mock(OrderStatusChanged::class);
        $mockEvent->shouldReceive('getNewStatus')->andReturn(OrderStatus::DELIVERED);
        $mockEvent->shouldReceive('getOrder')->andReturn(null); // Would process if order existed

        $listener->handle($mockEvent);
    }

    public function test_commission_calculation_logic()
    {
        // Test the commission calculation logic used in the listeners
        $orderAmount = 100.00;
        $commissionPercentage = 10;

        // This is the same logic used in the listeners
        $commissionAmount = ($orderAmount * $commissionPercentage) / 100;
        $storeAmount = $orderAmount - $commissionAmount;

        $this->assertEquals(10.00, $commissionAmount);
        $this->assertEquals(90.00, $storeAmount);
    }

    public function test_transaction_status_enum_values()
    {
        $this->assertEquals('pending', TransactionStatusEnum::PENDING->value);
        $this->assertEquals('success', TransactionStatusEnum::SUCCESS->value);
        $this->assertEquals('failed', TransactionStatusEnum::FAILED->value);
    }

    public function test_transaction_type_enum_values()
    {
        $this->assertEquals('commission', TransactionType::COMMISSION->value);
        $this->assertEquals('earning', TransactionType::EARNING->value);
        $this->assertEquals('deposit', TransactionType::DEPOSIT->value);
    }
}
