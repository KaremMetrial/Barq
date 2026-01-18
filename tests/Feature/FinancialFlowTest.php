<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Transaction;
use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\Couier\Models\Couier;
use Modules\Order\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Enums\TransactionStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinancialFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_flow_from_ready_for_delivery_to_delivered()
    {
        // Create test data
        $user = User::factory()->create();
        $store = Store::factory()->create([
            'commission_type' => 'percentage',
            'commission_amount' => 10 // 10% commission
        ]);
        $courier = Couier::factory()->create();

        // Create an order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'couier_id' => $courier->id,
            'total_amount' => 100.00,
            'delivery_fee' => 5.00,
            'status' => OrderStatus::PENDING
        ]);

        // Test READY_FOR_DELIVERY status
        $order->status = OrderStatus::READY_FOR_DELIVERY;
        $order->save();

        // Check store balance
        $storeBalance = $store->balance()->first();
        $this->assertNotNull($storeBalance);
        $this->assertEquals(90.00, $storeBalance->pending_balance); // 100 - 10% commission
        $this->assertEquals(90.00, $storeBalance->total_balance);

        // Check platform balance
        $platformBalance = \Modules\Balance\Models\Balance::where('balanceable_type', 'platform')->first();
        $this->assertNotNull($platformBalance);
        $this->assertEquals(10.00, $platformBalance->available_balance); // 10% commission
        $this->assertEquals(10.00, $platformBalance->total_balance);

        // Check transactions
        $storeTransaction = Transaction::where('transactionable_type', 'store')
            ->where('transactionable_id', $store->id)
            ->where('order_id', $order->id)
            ->first();
        $this->assertNotNull($storeTransaction);
        $this->assertEquals(TransactionType::EARNING->value, $storeTransaction->type);
        $this->assertEquals(90.00, $storeTransaction->amount);
        $this->assertEquals(TransactionStatusEnum::PENDING->value, $storeTransaction->status);

        $platformTransaction = Transaction::where('transactionable_type', 'platform')
            ->where('transactionable_id', 1)
            ->where('order_id', $order->id)
            ->first();
        $this->assertNotNull($platformTransaction);
        $this->assertEquals(TransactionType::COMMISSION->value, $platformTransaction->type);
        $this->assertEquals(10.00, $platformTransaction->amount);
        $this->assertEquals(TransactionStatusEnum::SUCCESS->value, $platformTransaction->status);

        // Test ON_THE_WAY status
        $order->status = OrderStatus::ON_THE_WAY;
        $order->save();

        // Check store balance after transfer to courier
        $storeBalance->refresh();
        $this->assertEquals(0.00, $storeBalance->pending_balance);
        $this->assertEquals(0.00, $storeBalance->total_balance);

        // Check courier balance
        $courierBalance = $courier->balance()->first();
        $this->assertNotNull($courierBalance);
        $this->assertEquals(90.00, $courierBalance->pending_balance);
        $this->assertEquals(90.00, $courierBalance->total_balance);

        // Check courier transaction
        $courierTransaction = Transaction::where('transactionable_type', 'courier')
            ->where('transactionable_id', $courier->id)
            ->where('order_id', $order->id)
            ->first();
        $this->assertNotNull($courierTransaction);
        $this->assertEquals(TransactionType::EARNING->value, $courierTransaction->type);
        $this->assertEquals(90.00, $courierTransaction->amount);
        $this->assertEquals(TransactionStatusEnum::PENDING->value, $courierTransaction->status);

        // Test DELIVERED status
        $order->status = OrderStatus::DELIVERED;
        $order->save();

        // Check final store balance
        $storeBalance->refresh();
        $this->assertEquals(90.00, $storeBalance->available_balance);
        $this->assertEquals(90.00, $storeBalance->total_balance);

        // Check final courier balance
        $courierBalance->refresh();
        $this->assertEquals(5.00, $courierBalance->available_balance); // Delivery fee only
        $this->assertEquals(5.00, $courierBalance->total_balance);
        $this->assertEquals(0.00, $courierBalance->pending_balance);

        // Check updated transactions
        $storeTransaction->refresh();
        $this->assertEquals(TransactionStatusEnum::SUCCESS->value, $storeTransaction->status);

        $courierTransaction->refresh();
        $this->assertEquals(TransactionStatusEnum::SUCCESS->value, $courierTransaction->status);

        // Check delivery fee transaction
        $deliveryFeeTransaction = Transaction::where('transactionable_type', 'courier')
            ->where('transactionable_id', $courier->id)
            ->where('order_id', $order->id)
            ->where('amount', 5.00)
            ->first();
        $this->assertNotNull($deliveryFeeTransaction);
        $this->assertEquals(TransactionType::EARNING->value, $deliveryFeeTransaction->type);
        $this->assertEquals(5.00, $deliveryFeeTransaction->amount);
        $this->assertEquals(TransactionStatusEnum::SUCCESS->value, $deliveryFeeTransaction->status);
    }
}
