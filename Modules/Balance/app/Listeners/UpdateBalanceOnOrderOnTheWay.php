<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only process when order status changes to on_the_way
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }
        Log::info('from UpdateBalanceOnOrderOnTheWay listener');

        Log::info('status : ' . $newStatus);
        Log::info('OrderStatus : ' . OrderStatus::ON_THE_WAY->value);
        if ($newStatus != OrderStatus::ON_THE_WAY->value) {
            return;
        }

        $order = $event->order;
        Log::info('order : ' . $order->order_number);
        DB::transaction(function () use ($order) {
            // Calculate store amount (order amount minus commission)
            $storeAmount = $order->total_amount - $order->store->calculateCommission($order->total_amount);
            Log::info('storeAmount : ' . $storeAmount);

            // Update store balance - deduct from pending
            $store = $order->store;
            Log::info('store : ' . $store->name);
            if ($store) {
                $storeBalance = $store->balance()->firstOrCreate([], [
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'total_balance' => 0,
                ]);
                Log::info('storeBalance : ' . $storeBalance->pending_balance);
                // Deduct from store's pending balance
                $storeBalance->decrement('pending_balance', $storeAmount);
                $storeBalance->decrement('total_balance', $storeAmount);

                // Create transaction record for store
                Transaction::create([
                    'transactionable_type' => 'store',
                    'transactionable_id' => $store->id,
                    'type' => TransactionType::DECREMENT,
                    'amount' => $storeAmount,
                    'currency' => $store->getCurrencyCode() ?? 'USD',
                    'translations' => [
                      'ar' => ['description' =>"تم استلام دفعه الطلب من عامل التوصيل #{$order->order_number}"],
                      'en' => ['description' => "Order payment paid by courier for order #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);
                Log::info('transactions!');

            }

            // Update courier balance - add to pending
            $courier = $order->courier;
            Log::info('courier : ' . $courier->name);
            if ($courier) {
                $courierBalance = $courier->balance()->firstOrCreate([], [
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'total_balance' => 0,
                ]);

                Log::info('courierBalance : ' . $courierBalance->pending_balance);

                // Add to courier's pending balance
                $courierBalance->increment('pending_balance', $storeAmount);
                $courierBalance->increment('total_balance', $storeAmount);

                Transaction::create([
                    'transactionable_type' => 'courier',
                    'transactionable_id' => $courier->id,
                    'type' => TransactionType::INCREMENT,
                    'amount' => $storeAmount,
                    'currency' => $courier->store->getCurrencyCode() ?? 'USD',
                    'translations' => [
                        'en' => ['description' => "Order payment paid by courier for store #{$order->store->name} . for order #{$order->order_number}"],
                        'ar' => ['description' => "تم دفع دفعة الطلب من عامل التوصيل للمتجر #{$order->store->name} . للطلب رقم #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);

                // Create transaction record for courier
                Transaction::create([
                    'transactionable_type' => 'courier',
                    'transactionable_id' => $courier->id,
                    'type' => TransactionType::INCREMENT,
                    'amount' => $storeAmount,
                    'currency' => $courier->store->currency_code ?? 'USD',
                    'translations' => [
                       'en' => ['description' => "Order payment received for order #{$order->order_number}"],
                       'ar' => ['description' => "تم استلام دفعة الطلب للطلب رقم #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);
                Log::info('transactions!');
            }
        });
    }
}
