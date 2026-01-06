<?php

namespace Modules\Order\Observers;

use Carbon\Carbon;
use App\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Illuminate\Support\Facades\Log;
use Modules\Order\Jobs\CancelOrderJob;
use Modules\User\Services\LoyaltyService;
use Modules\Order\Models\OrderStatusHistory;
use Modules\Order\Events\OrderNotAcceptedOnTime;
use Modules\Order\Events\OrderAssignmentToCourier;
use Modules\Order\Services\OrderNotificationService;
use Modules\Store\Events\NewOrderEvent;

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

        if ($order->store) {
            $this->orderNotificationService->sendNewOrderNotificationToStore(
                $order->store,
                $order->id,
                $order->total_amount
            );
        }
        $this->scheduleAutoCancellation($order);

        broadcast(new NewOrderEvent($order));
    }

    private function scheduleAutoCancellation(Order $order): void
    {
        $cancellationWindowMinutes = (int) config('settings.order.cancellation_window_minutes', 5);
        // Dispatch the event
        CancelOrderJob::dispatch($order)
            ->delay(now()->addMinutes($cancellationWindowMinutes));
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

        if ($order->isDirty('status') && $order->store) {
            $this->orderNotificationService->sendOrderStatusNotificationToStore(
                $order->store,
                $order->id,
                $order->status->value
            );
        }


        // Check if status has changed
        if ($order->isDirty('status')) {
         if (auth('user')->check()) {
                $changedById = auth('user')->id();
                $changedByType = 'user';
            } elseif (auth('admin')->check()) {
                $changedById = auth('admin')->id();
                $changedByType = 'admin';
            } elseif (auth('vendor')->check()) {
                $changedById = auth('vendor')->id();
                $changedByType = 'vendor';
            } elseif (auth('sanctum')->check()) {
                // Check if it's an admin or vendor through sanctum
                $user = auth('sanctum')->user();
                if ($user && method_exists($user, 'tokenCan')) {
                    if ($user->tokenCan('admin')) {
                        $changedById = $user->id;
                        $changedByType = 'admin';
                    } elseif ($user->tokenCan('vendor')) {
                        $changedById = $user->id;
                        $changedByType = 'vendor';
                    }
                }
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status->value,
                'changed_at' => now(),
                'note' => 'Status updated',
                'changed_by' => $changedByType . ':' . $changedById
            ]);

            if ($order->status === OrderStatus::CANCELLED && $order->user) {
                if ($changedByType === 'user') {
                    if ($order->user->shouldBlockForCancellations()) {
                        $order->user->blockForExcessiveCancellations();
                    }
                }
            }


            if ($order->status === OrderStatus::CANCELLED && $order->user) {
                if ($order->user->shouldBlockForCancellations()) {
                    $order->user->blockForExcessiveCancellations();
                }
            }


            // Award referral points when order is delivered
            if ($order->status === OrderStatus::DELIVERED && $order->user && $order->user->referral_id) {
                $this->loyaltyService->awardReferralPoints(
                    $order->user->referral_id,
                    $order->user_id,
                    $order
                );
            }
            if ($order->isDirty('courier_id')) {
                $this->handleCourierUpdate($order);
            }
        }
    }
    private function handleCourierUpdate(Order $order): void
    {
        if ($order->courier) {
            OrderAssignmentToCourier::dispatch($order, $order->courier, $unreadMessagesCount = (int) $order->courierUnreadMessagesCount(), $order->courier->getCurrentLatitudeAttribute(), $order->courier->getCurrentLongitudeAttribute());
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
