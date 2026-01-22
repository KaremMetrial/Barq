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

            // Calculate amounts using fixed model methods to avoid unit errors
            $platformCommissionFromOrder = $store->calculateCommission($order->total_amount);
            $storeAmount = $order->total_amount - $platformCommissionFromOrder;

            $deliveryFee = $order->delivery_fee ?? 0;
            $courierCommissionFromDelivery = $courier->calculateCommission($deliveryFee);
            $courierProfit = $deliveryFee - $courierCommissionFromDelivery;

            // Get balances
            $courierBalance = $courier->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            $storeBalance = $store->balance()->firstOrCreate([], [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_balance' => 0,
            ]);

            $platformBalance = \Modules\Balance\Models\Balance::firstOrCreate(
                ['balanceable_type' => 'platform', 'balanceable_id' => 1],
                ['available_balance' => 0, 'pending_balance' => 0, 'total_balance' => 0]
            );

            // ============================================
            // 1. SETTLE STORE EARNINGS
            // ============================================
            // We assume the courier already paid the store cash (at OnTheWay pickup)
            // or the store is paid electronically. In either case, we credit their wallet
            // for the earning, and if they got cash, we'd debit it (done via Courier wallet decrement).
            $storeBalance->increment('available_balance', $storeAmount);
            $storeBalance->increment('total_balance', $storeAmount);

            Transaction::create([
                'transactionable_type' => 'store',
                'transactionable_id' => $store->id,
                'type' => TransactionType::EARNING,
                'amount' => $storeAmount,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "أرباح من الطلب رقم #{$order->order_number}"],
                    'en' => ['description' => "Earnings from order #{$order->order_number}"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);

            // ============================================
            // 2. SETTLE COURIER DEBT/PROFIT
            // ============================================
            // Courier collected cash (Total + Delivery Fee) = 1000 + 50 = 1050
            // Courier paid Store cash = 900
            // Courier owes Platform = 100 (Store Commission) + 5 (Courier Commission) = 105

            // To reflect this in wallet:
            // Debit courier for Store Amount (because they "collected" it or paid it from pocket)
            // Debit courier for Platform Commissions
            // Credit courier for Delivery Fee (their gross pay)

            $totalDebitToCourier = $storeAmount + $platformCommissionFromOrder + $courierCommissionFromDelivery;
            $courierBalance->decrement('available_balance', $totalDebitToCourier);
            $courierBalance->decrement('total_balance', $totalDebitToCourier);

            // Credit net delivery fee? No, credit gross then debit commission is clearer.
            $courierBalance->increment('available_balance', $deliveryFee);
            $courierBalance->increment('total_balance', $deliveryFee);

            Transaction::create([
                'transactionable_type' => 'courier',
                'transactionable_id' => $courier->id,
                'type' => TransactionType::DECREMENT,
                'amount' => $totalDebitToCourier,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "تسوية مستحقات الطلب رقم #{$order->order_number} (مبلغ المتجر + عمولة المنصة)"],
                    'en' => ['description' => "Order settlement for #{$order->order_number} (Store amount + Platform commission)"],
                ],
                'status' => TransactionStatusEnum::SUCCESS->value,
                'order_id' => $order->id
            ]);

            Transaction::create([
                'transactionable_type' => 'courier',
                'transactionable_id' => $courier->id,
                'type' => TransactionType::INCREMENT,
                'amount' => $deliveryFee,
                'currency' => $store->getCurrencyCode() ?? 'USD',
                'translations' => [
                    'ar' => ['description' => "رسوم التوصيل للطلب رقم #{$order->order_number}"],
                    'en' => ['description' => "Delivery fee for order #{$order->order_number}"],
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
