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
            $store = $order->store;

            if (!$store) {
                return;
            }

            // Calculate amounts
            $platformCommissionFromOrder = $store->calculateCommission($order->total_amount);
            $storeAmount = $order->total_amount - $platformCommissionFromOrder;

            // Get store balance
            $storeBalance = $store->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            // Credit store balance
            $storeBalance->increment('available_balance', $storeAmount);
            $storeBalance->increment('total_balance', $storeAmount);

            Transaction::create([
                'transactionable_type' => 'store',
                'transactionable_id' => $store->id,
                'type' => TransactionType::EARNING,
                'amount' => $storeAmount,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "مستحقات معلقة للطلب رقم #{$order->order_number} (جاهز للتوصيل)"],
                    'en' => ['description' => "Pending earnings for order #{$order->order_number} (Ready for delivery)"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);
        });
    }
}
