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
     * Final settlement when order is delivered.
     * Courier collected cash from customer and now must settle with platform.
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
            $courier = $order->courier;
            $store = $order->store;

            if (!$courier || !$store) {
                return;
            }

            // Calculate amounts
            $platformCommissionFromOrder = $store->calculateCommission($order->total_amount);
            $deliveryFee = $order->delivery_fee ?? 0;
            $courierCommissionFromDelivery = $courier->calculateCommission($deliveryFee);
            $courierProfit = $deliveryFee - $courierCommissionFromDelivery;
            $totalCollectedFromCustomer = $order->total_amount + $deliveryFee;

            // Get balances
            $courierBalance = $courier->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            $platformBalance = \Modules\Balance\Models\Balance::firstOrCreate(
                ['balanceable_type' => 'platform', 'balanceable_id' => 1],
                ['available_balance' => 0, 'pending_balance' => 0, 'total_balance' => 0]
            );

            // ============================================
            // 2. SETTLE COURIER DEBT/PROFIT
            // ============================================
            // Courier outcome calculation:
            // Current balance has +(Total - StoreComm) from OnTheWay
            // We now subtract the cash they collected from customer
            // And add their net profit from delivery

            $courierBalance->decrement('available_balance', $totalCollectedFromCustomer);
            $courierBalance->decrement('total_balance', $totalCollectedFromCustomer);

            $courierBalance->increment('available_balance', $courierProfit);
            $courierBalance->increment('total_balance', $courierProfit);

            Transaction::create([
                'transactionable_type' => 'courier',
                'transactionable_id' => $courier->id,
                'type' => TransactionType::DECREMENT,
                'amount' => $totalCollectedFromCustomer,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "تحصيل مبلغ الطلب رقم #{$order->order_number} من العميل"],
                    'en' => ['description' => "Order amount collection for #{$order->order_number} from customer"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);

            Transaction::create([
                'transactionable_type' => 'courier',
                'transactionable_id' => $courier->id,
                'type' => TransactionType::INCREMENT,
                'amount' => $courierProfit,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "صافي ربح التوصيل للطلب رقم #{$order->order_number}"],
                    'en' => ['description' => "Net delivery profit for order #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);

            // ============================================
            // 3. SETTLE PLATFORM REVENUE
            // ============================================
            $totalPlatformRealized = $platformCommissionFromOrder + $courierCommissionFromDelivery;
            $platformBalance->increment('available_balance', $totalPlatformRealized);
            $platformBalance->increment('total_balance', $totalPlatformRealized);

            Transaction::create([
                'transactionable_type' => 'platform',
                'transactionable_id' => 1,
                'type' => TransactionType::COMMISSION,
                'amount' => $totalPlatformRealized,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "إجمالي عمولة الطلب رقم #{$order->order_number}"],
                    'en' => ['description' => "Total commission for order #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);
        });
    }

    /**
     * Calculate courier commission
     */
    private function calculateCourierCommission($courier, $deliveryFee)
    {
        return $courier->calculateCommission($deliveryFee);
    }
}
