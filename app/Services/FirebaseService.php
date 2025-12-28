<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

class FirebaseService
{
    public function __construct(protected Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send push notification to one or multiple device tokens.
     *
     * @param string|array|\Illuminate\Support\Collection $deviceTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToDevice($deviceTokens, string $title, string $body, array $data = []): bool
    {
        Log::info('🚀 FKN Data', [
            'data' => $data,
        ]);
        // Normalize tokens
        if ($deviceTokens instanceof \Illuminate\Support\Collection) {
            $tokens = $deviceTokens->toArray();
        } elseif (is_string($deviceTokens)) {
            $tokens = [$deviceTokens];
        } elseif (is_array($deviceTokens)) {
            $tokens = $deviceTokens;
        } else {
            Log::warning('❌ Invalid device tokens type', ['tokens' => $deviceTokens]);
            return false;
        }

        if (empty($tokens)) {
            Log::warning('❌ No device tokens provided', [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
            return false;
        }

        try {
            // ----------------------------------------------------------
            // ANDROID CONFIG  (⬅ هذا اللي يخلي onMessage شغال)
            // ----------------------------------------------------------
        //    $androidConfig = AndroidConfig::fromArray([
        //         'priority' => 'high',
        //         'notification' => [
        //             'title' => $title,
        //             'body'  => $body,
        //             'channel_id' => 'default_channel',
        //             'sound' => 'default',
        //         ],
        //     ]);

            // ----------------------------------------------------------
            // iOS CONFIG
            // ----------------------------------------------------------
            // $apnsConfig = ApnsConfig::fromArray([
            //     'headers' => [
            //         'apns-priority' => '10',
            //     ],
            //     'payload' => [
            //         'aps' => [
            //             'alert' => [
            //                 'title' => $title,
            //                 'body'  => $body,
            //             ],
            //             'sound' => 'default',
            //             'content-available' => 1,
            //         ],
            //     ],
            // ]);

            // ----------------------------------------------------------
            // FINAL MESSAGE
            // ----------------------------------------------------------
            $message = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($data);
                // ->withAndroidConfig($androidConfig)
                // ->withApnsConfig($apnsConfig);

            // ----------------------------------------------------------
            // SEND
            // ----------------------------------------------------------
            $sendReport = $this->messaging->sendMulticast($message, $tokens);

            // Logging
            Log::info('✅ Firebase notification sent', [
                'tokens'         => $tokens,
                'title'          => $title,
                'body'           => $body,
                'data'           => $data,
                'success_count'  => $sendReport->successes()->count(),
                'failure_count'  => $sendReport->failures()->count(),
            ]);

            return $sendReport->successes()->count() > 0;

        } catch (InvalidMessage $e) {
            Log::error('❌ Firebase send error', [
                'tokens' => $tokens,
                'title'  => $title,
                'body'   => $body,
                'data'   => $data,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }
    public function fcmTest()
    {
        $tokens = ['foB8bWoNSkOp0xWPEOq47p:APA91bGQ_FIVIQC70KAPAJgC5G1sprujHak9RvvWXaJsjXEq92-ABjakgwtlqu3Qvufn0h28Pul45Wrh8mc9iDmYVcOxk1QiPqVdW_Fkio_o9mcKy-uGT9s'];

        $title = "New Order Received!";
        $body = "You have a new order";
        $data = [
            'order_id' => 1,
            'store_id' => 1,
            'notification_type' => 'new_order',
        ];
        $message = CloudMessage::new()
            ->withNotification(FirebaseNotification::create($title, $body))
            ->withData($data);
        $sendReport = $this->messaging->sendMulticast($message, $tokens);
        dd($sendReport);
    }
}
