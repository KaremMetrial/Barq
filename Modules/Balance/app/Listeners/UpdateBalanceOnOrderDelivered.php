<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
use App\Enums\PlanTypeEnum;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderStatusChanged;

class UpdateBalanceOnOrderDelivered implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only process when order status changes to delivered
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }

        if ($newStatus != OrderStatus::DELIVERED->value) {
            return;
        }

        $order = $event->order;

        DB::transaction(function () use ($order) {
            // Calculate store amount (order amount minus commission)
            $storeAmount = $order->total_amount - $order->store->calculateCommission($order->total_amount);
            $commissionAmount = $order->store->calculateCommission($order->total_amount);

            // Update courier balance - deduct store amount and courier commission, add delivery fee
            $courier = $order->courier;
            if ($courier) {
                $courierBalance = $courier->balance()->firstOrCreate([], [
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'total_balance' => 0,
                ]);

                // Calculate courier commission
                $courierCommission = $this->calculateCourierCommission($courier, $order->total_amount);

                // Deduct store amount from courier's available balance
                $courierBalance->decrement('available_balance', $storeAmount);
                $courierBalance->decrement('total_balance', $storeAmount);

                // Create transaction record for store payment by courier
                Transaction::create([
                    'transactionable_type' => 'courier',
                    'transactionable_id' => $courier->id,
                    'type' => TransactionType::DECREMENT,
                    'amount' => $storeAmount,
                    'currency' => $courier->store->getCurrencyCode() ?? 'USD',
                    'translations' => [
                        'en' => ['description' => "Payment for order you paid for store #{$order->order_number}"],
                        'ar' => ['description' => "التي تم دفعها للمطعم دفعة للطلب رقم #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);

                // Deduct courier commission from courier's available balance
                $courierBalance->decrement('available_balance', $courierCommission);
                $courierBalance->decrement('total_balance', $courierCommission);

                // Create transaction record for courier commission
                Transaction::create([
                    'transactionable_type' => 'courier',
                    'transactionable_id' => $courier->id,
                    'type' => TransactionType::DECREMENT,
                    'amount' => $courierCommission,
                    'currency' => $courier->store->getCurrencyCode() ?? 'USD',
                    'translations' => [
                        'en' => ['description' => "Courier commission for order #{$order->order_number}"],
                        'ar' => ['description' => "عمولة عامل التوصيل للطلب رقم #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);

                // Add delivery fee to courier's available balance
                $deliveryFee = $this->calculateCourierPayment($order);
                $courierBalance->increment('available_balance', $deliveryFee);
                $courierBalance->increment('total_balance', $deliveryFee);

                // Create transaction record for delivery fee
                Transaction::create([
                    'transactionable_type' => 'courier',
                    'transactionable_id' => $courier->id,
                    'type' => TransactionType::EARNING,
                    'amount' => $deliveryFee,
                    'currency' => $courier->store->getCurrencyCode() ?? 'USD',
                    'translations' => [
                        'en' => ['description' => "Delivery fee for order #{$order->order_number}"],
                        'ar' => ['description' => "رسوم توصيل للطلب رقم #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);
            }

            // Update store balance - add to available
            $store = $order->store;
            $storeBalance = $store->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            $storeBalance->increment('available_balance', $storeAmount);
            $storeBalance->increment('total_balance', $storeAmount);

            // Create transaction record for store
            Transaction::create([
                'transactionable_type' => 'store',
                'transactionable_id' => $store->id,
                'type' => TransactionType::EARNING,
                'amount' => $storeAmount,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'en' => ['description' => "Earnings from order #{$order->order_number}"],
                    'ar' => ['description' => "أرباح من الطلب رقم #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);

            // Update platform balance with commission
            $platformBalance = \Modules\Balance\Models\Balance::firstOrCreate(
                ['balanceable_type' => 'platform', 'balanceable_id' => 1],
                ['available_balance' => 0, 'pending_balance' => 0, 'total_balance' => 0]
            );

            $platformBalance->increment('available_balance', $commissionAmount);
            $platformBalance->increment('total_balance', $commissionAmount);

            // Create transaction record for platform commission
            Transaction::create([
                'transactionable_type' => 'platform',
                'transactionable_id' => 1,
                'type' => TransactionType::COMMISSION,
                'amount' => $commissionAmount,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'en' => ['description' => "Commission from order #{$order->order_number}"],
                    'ar' => ['description' => "عمولة من الطلب رقم #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);
        });
    }

    /**
     * Calculate courier payment (delivery fee)
     */
    private function calculateCourierPayment($order)
    {
        return $order->delivery_fee ?? 0;
    }

    /**
     * Calculate courier commission based on courier's commission settings
     */
    private function calculateCourierCommission($courier, $orderAmount)
    {
        if ($courier->commission_type === PlanTypeEnum::COMMISSION) {
            return ($orderAmount * $courier->commission_amount) / 100;
        } elseif ($courier->commission_type === PlanTypeEnum::SUBSCRIPTION) {
            return $courier->commission_amount;
        }
        return 0;
    }
}
