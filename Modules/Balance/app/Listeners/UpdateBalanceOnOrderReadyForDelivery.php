<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderStatusChanged;

class UpdateBalanceOnOrderReadyForDelivery implements ShouldQueue
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
        // Only process when order status changes to ready_for_delivery
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }

        if ($newStatus != OrderStatus::READY_FOR_DELIVERY->value) {
            return;
        }

        $order = $event->order;

        DB::transaction(function () use ($order) {
            // Calculate store amount (order amount minus commission)
            $storeAmount = $order->total_amount - $order->store->calculateCommission($order->total_amount);

            // Update store balance with order amount (minus commission)
            $store = $order->store;
            if ($store) {
                $storeBalance = $store->balance()->firstOrCreate([], [
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'total_balance' => 0,
                ]);

                $storeBalance->increment('pending_balance', $storeAmount);
                $storeBalance->increment('total_balance', $storeAmount);

                // Create transaction record for store
                Transaction::create([
                    'transactionable_type' => 'store',
                    'transactionable_id' => $store->id,
                    'type' => TransactionType::INCREMENT,
                    'amount' => $storeAmount,
                    'currency' => $store->getCurrencyCode() ?? 'USD',
                    'translations' => [
                      'en' => ['description' => "Order payment received for order #{$order->order_number}"],
                      'ar' => ['description' => "تم استلام دفعة الطلب للطلب رقم #{$order->order_number}"],
                    ],
                    'status' => TransactionStatusEnum::SUCCESS->value,
                    'order_id' => $order->id
                ]);
            }

            // Update platform balance with commission
            $commissionAmount = $order->store->calculateCommission($order->total_amount);

            // For platform, we'll use a generic balance model or create a platform-specific one
            // This is a simplified approach - in a real system, you'd have a Platform model
            $platformBalance = \Modules\Balance\Models\Balance::firstOrCreate(
                ['balanceable_type' => 'platform', 'balanceable_id' => 1],
                ['available_balance' => 0, 'pending_balance' => 0, 'total_balance' => 0]
            );

            $platformBalance->increment('available_balance', $commissionAmount);
            $platformBalance->increment('total_balance', $commissionAmount);

            // Create transaction record for platform
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
}
