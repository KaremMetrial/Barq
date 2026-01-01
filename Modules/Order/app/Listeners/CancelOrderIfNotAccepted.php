<?php

namespace Modules\Order\Listeners;

use App\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderNotAcceptedOnTime;
use Modules\Order\Models\OrderStatusHistory;
use Modules\Order\Services\OrderNotificationService;

class CancelOrderIfNotAccepted implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderNotAcceptedOnTime $event): void
    {
        $order = $event->order;

        // تأكد من أن الطلب لا يزال قيد الانتظار
        if ($order->status !== OrderStatus::PENDING) {
            \Log::info('Order status already changed, skipping auto-cancellation', [
                'order_id' => $order->id,
                'current_status' => $order->status->value
            ]);
            return;
        }

        \DB::transaction(function () use ($order, $event) {
            // تحديث حالة الطلب إلى ملغي
            $order->update([
                'status' => OrderStatus::CANCELLED,
            ]);

            // تسجيل في تاريخ الحالات
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => OrderStatus::CANCELLED,
                'changed_at' => now(),
                'changed_by' => 'system:timeout',
                'note' => $event->timeoutReason
            ]);

            // تسجيل في السجلات
            \Log::info('Order auto-cancelled due to timeout', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'store_id' => $order->store_id,
                'user_id' => $order->user_id,
                'reason' => $event->timeoutReason
            ]);
        });

        // إرسال إشعارات للمستخدم والمتجر
        $this->sendNotifications($order, $event->timeoutReason);
    }

    /**
     * إرسال إشعارات لإلغاء الطلب
     */
    private function sendNotifications(Order $order, string $reason): void
    {
        try {
            $notificationService = app(OrderNotificationService::class);

            // إشعار المستخدم
            if ($order->user) {
                $notificationService->sendOrderStatusNotification(
                    $order->user,
                    $order->id,
                    'Order cancelled: ' . $reason
                );
            }

            // إشعار المتجر
            if ($order->store) {
                $notificationService->sendOrderStatusNotificationToStore(
                    $order->store,
                    $order->id,
                    'Order auto-cancelled: ' . $reason
                );
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send auto-cancellation notifications', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إعادة المحاولة في حالة الفشل
     */
    public function retryUntil(): \Carbon\Carbon
    {
        return now()->addMinutes(5);
    }

    /**
     * معالجة الفشل في الـ Job
     */
    public function failed(OrderNotAcceptedOnTime $event, \Throwable $exception): void
    {
        \Log::critical('Failed to process order auto-cancellation', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
