<?php

namespace Modules\Order\Observers;

use App\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderStatusHistory;
use Modules\Order\Services\OrderNotificationService;
use Modules\User\Services\LoyaltyService;

class OrderObserver
{

    public function __construct(
        protected OrderNotificationService $orderNotificationService,
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Handle the OrderObserver "created" event.
     */
    public function created(Order $order): void
    {
        $order->refresh();
        // Create initial status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'changed_at' => now(),
            'note' => 'Order created',
        ]);
        if ($order->user) {
            $this->orderNotificationService->sendOrderStatusNotification(
                $order->user,
                $order->id,
                'Order created'
            );
        }
    }

    /**
     * Handle the OrderObserver "updated" event.
     */
    public function updated(Order $order): void
    {
        $order->refresh();
        if ($order->user) {
            $this->orderNotificationService->sendOrderStatusNotification(
                $order->user,
                $order->id,
                $order->status->value
            );
        }
        // Check if status has changed
        if ($order->isDirty('status')) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status->value,
                'changed_at' => now(),
                'note' => 'Status updated',
            ]);

            // Award referral points when order is delivered
            if ($order->status === OrderStatus::DELIVERED && $order->user && $order->user->referral_id) {
                $this->loyaltyService->awardReferralPoints(
                    $order->user->referral_id,
                    $order->user_id,
                    $order
                );
            }
        }
    }

    /**
     * Handle the OrderObserver "deleted" event.
     */
    public function deleted(Order $order): void {}

    /**
     * Handle the OrderObserver "restored" event.
     */
    public function restored(Order $order): void {}

    /**
     * Handle the OrderObserver "force deleted" event.
     */
    public function forceDeleted(Order $order): void {}
}
