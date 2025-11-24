<?php

namespace Modules\Order\Services;

use Modules\User\Models\User;
use App\Jobs\SendFcmNotificationJob;
use App\Notifications\FirebasePushNotification;

class OrderNotificationService
{
    /**
     * Send order status notification to a user.
     *
     * @param User $user The user to notify
     * @param string $orderId The order id or reference
     * @param string $status The order status update message
     */
    public function sendOrderStatusNotification(User $user, string $orderId, string $status)
    {
        $tokens = $user->tokens()
            ->where('notification_active', true)
            ->whereNotNull('fcm_device')
            ->pluck('fcm_device');

        if ($tokens->isEmpty()) {
            return;
        }

        $title = "Order #{$orderId} Update";
        $body = "Your order status is now: {$status}";

        $data = [
            'order_id' => $orderId,
            'status' => $status,
        ];
        SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);
    }
}
