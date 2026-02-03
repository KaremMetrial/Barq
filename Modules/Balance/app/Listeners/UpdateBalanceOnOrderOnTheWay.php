<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionStatusEnum;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderStatusChanged;

class UpdateBalanceOnOrderOnTheWay implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     * When courier picks up order and pays store in cash.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only process when order status changes to on_the_way
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }

        if ($newStatus != OrderStatus::ON_THE_WAY->value) {
            return;
        }

        $order = $event->order;

        DB::transaction(function () use ($order) {
            $store = $order->store;
            $courier = $order->courier;

            if (!$store || !$courier) {
                return;
            }

            // Calculate amounts
            $platformCommissionFromOrder = $store->calculateCommission($order->total_amount);
            $storeAmount = $order->total_amount - $platformCommissionFromOrder;

            // Get balances
            $storeBalance = $store->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            $courierBalance = $courier->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            // 1. Debit store balance (because they received cash from courier)
            $storeBalance->decrement('available_balance', $storeAmount);
            $storeBalance->decrement('total_balance', $storeAmount);

            // 2. Credit courier balance (because they paid cash to store)
            $courierBalance->increment('available_balance', $storeAmount);
            $courierBalance->increment('total_balance', $storeAmount);

            // Record Transactions
            Transaction::create([
                'transactionable_type' => 'store',
                'transactionable_id' => $store->id,
                'type' => TransactionType::DECREMENT,
                'amount' => $storeAmount,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "تحصيل نقدي من المندوب للطلب رقم #{$order->order_number}"],
                    'en' => ['description' => "Cash collection from courier for order #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);

            Transaction::create([
                'transactionable_type' => 'courier',
                'transactionable_id' => $courier->id,
                'type' => TransactionType::INCREMENT,
                'amount' => $storeAmount,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "دفع نقدي للمتجر للطلب رقم #{$order->order_number}"],
                    'en' => ['description' => "Cash payment to store for order #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);
        });
    }
}
