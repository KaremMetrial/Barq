<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
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

        if ($newStatus !== OrderStatus::DELIVERED->value) {
            return;
        }

        $order = $event->order;

        DB::transaction(function () use ($order) {
            // Update store balance with commission
            $store = $order->store;
            if ($store) {
                $commissionAmount = $store->calculateCommission($order->total_amount);

                $storeBalance = $store->balance()->firstOrCreate([], [
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'total_balance' => 0,
                ]);

                $storeBalance->increment('available_balance', $commissionAmount);
                $storeBalance->increment('total_balance', $commissionAmount);

                // Create transaction record
                $transactionService = app(\App\Services\TransactionService::class);
                $transactionService->createForStore($store, [
                    'type' => 'commission',
                    'amount' => $commissionAmount,
                    'currency' => $store->currency_code ?? 'USD',
                    'description' => "Commission from order #{$order->order_number}",
                    'status' => 'completed'
                ]);
            }

            // Update courier balance if applicable
            $courier = $order->courier;
            if ($courier) {
                $courierAmount = $this->calculateCourierPayment($order);

                $courierBalance = $courier->balance()->firstOrCreate([], [
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'total_balance' => 0,
                ]);

                $courierBalance->increment('available_balance', $courierAmount);
                $courierBalance->increment('total_balance', $courierAmount);

                // Create transaction record
                $transactionService = app(\App\Services\TransactionService::class);
                $transactionService->createForCourier($courier, [
                    'type' => 'delivery_fee',
                    'amount' => $courierAmount,
                    'currency' => $courier->store->currency_code ?? 'USD',
                    'description' => "Delivery payment for order #{$order->order_number}",
                    'status' => 'completed'
                ]);
            }
        });
    }
    private function calculateCourierPayment($order)
    {
        return $order->delivery_fee ?? 0;
    }
}
