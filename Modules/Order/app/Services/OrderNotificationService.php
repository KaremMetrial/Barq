<?php

namespace Modules\Order\Services;

use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\Vendor\Models\Vendor;
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

    public function sendNewOrderNotificationToStore(Store $store, string $orderId, float $orderAmount)
    {
        $vendors = $store->vendors()
            ->where('is_active', true)
            ->get();

        if ($vendors->isEmpty()) {
            return;
        }

        $title = "New Order Received!";
        $body = "You have a new order";

        $data = [
            'order_id' => $orderId,
            'store_id' => $store->id,
            'notification_type' => 'new_order',
        ];

        foreach ($vendors as $vendor) {
            $this->sendNotificationToVendor($vendor, $title, $body, $data);
        }
    }
    protected function sendNotificationToVendor(Vendor $vendor, string $title, string $body, array $data = [])
    {
        $tokens = $vendor->tokens()
            ->whereNotNull('fcm_device')
            ->pluck('fcm_device');

        if ($tokens->isEmpty()) {
            return;
        }

        SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);
    }
    public function sendOrderStatusNotificationToStore(Store $store, string $orderId, string $status)
    {
        $vendors = $store->vendors()
            ->where('is_active', true)
            ->get();

        if ($vendors->isEmpty()) {
            return;
        }

        $title = "Order #{$orderId} Update";
        $body = "Order status is now: {$status}";

        $data = [
            'order_id' => $orderId,
            'status' => $status,
            'store_id' => $store->id,
            'notification_type' => 'order_status_update',
        ];

        foreach ($vendors as $vendor) {
            $this->sendNotificationToVendor($vendor, $title, $body, $data);
        }
    }

}
