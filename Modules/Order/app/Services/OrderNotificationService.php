<?php

namespace Modules\Order\Services;

use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\Vendor\Models\Vendor;
use App\Jobs\SendFcmNotificationJob;
use App\Notifications\FirebasePushNotification;
use Illuminate\Support\Facades\Log;

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
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        // Group tokens by language
        $tokensByLanguage = $tokens->groupBy('language_code');

        // Send notifications for each language group
        foreach ($tokensByLanguage as $languageCode => $languageTokens) {
            $fcmDevices = $languageTokens->pluck('fcm_device');
            $localizedMessages = $this->getLocalizedMessages($languageCode, $orderId, $status);

            $data = [
                'order_id' => $orderId,
                'status' => $status,
                'language_code' => $languageCode,
            ];

            SendFcmNotificationJob::dispatch($fcmDevices, $localizedMessages['title'], $localizedMessages['body'], $data);
        }
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
            ->where('notification_active', true)
            ->whereNotNull('fcm_device')
            ->get();
        if ($tokens->isEmpty()) {
            return;
        }

        // Group tokens by language for vendors too
        $tokensByLanguage = $tokens->groupBy('language_code');

        // Send notifications for each language group
        foreach ($tokensByLanguage as $languageCode => $languageTokens) {
            $fcmDevices = $languageTokens->pluck('fcm_device');
            $localizedMessages = $this->getLocalizedMessages($languageCode, $data['order_id'] ?? '', $data['status'] ?? '');

            // Use localized messages for title/body if it's an order status notification
            $notificationTitle = isset($data['status']) ? $localizedMessages['title'] : $title;
            $notificationBody = isset($data['status']) ? $localizedMessages['body'] : $body;

            $notificationData = array_merge($data, ['language_code' => $languageCode]);

            SendFcmNotificationJob::dispatch($fcmDevices, $notificationTitle, $notificationBody, $notificationData);
        }
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

    /**
     * Get localized messages based on language code
     */
    protected function getLocalizedMessages(?string $languageCode, string $orderId, string $status): array
    {
        $languageCode = $languageCode ?: 'en'; // Default to English

        $messages = [
            'en' => [
                'title' => "Order #{$orderId} Update",
                'body' => "Your order status is now: {$status}",
            ],
            'ar' => [
                'title' => "تحديث الطلب #{$orderId}",
                'body' => "حالة طلبك الآن: {$status}",
            ],
            // Add more languages as needed
        ];

        return $messages[$languageCode] ?? $messages['en'];
    }

}
