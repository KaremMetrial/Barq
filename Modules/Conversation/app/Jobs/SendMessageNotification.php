<?php

namespace Modules\Conversation\Jobs;

use Illuminate\Bus\Queueable;
use App\Jobs\SendFcmNotificationJob;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Conversation\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Conversation\Models\Conversation;

class SendMessageNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Message $message,
        protected Conversation $conversation,
        protected $senderGuard
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all recipients (conversation participants except sender)
        $recipients = $this->getConversationRecipients();

        if ($recipients->isEmpty()) {
            return;
        }

        $title = 'New Message Received';
        $body = $this->message->content;
        $data = [
            'type' => 'new_message',
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'order_id' => $this->conversation->order_id ?? null,
            'sender_guard' => $this->senderGuard
        ];

        // Send notification to each recipient
        foreach ($recipients as $recipientData) {
            $deviceTokens = $this->getRecipientDeviceTokens($recipientData);

            if ($deviceTokens->isNotEmpty()) {
                SendFcmNotificationJob::dispatch(
                    $deviceTokens->pluck('fcm_device'),
                    $title,
                    $body,
                    $data
                );
            }
        }
    }

    /**
     * Get all conversation recipients except the sender
     */
    private function getConversationRecipients(): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Add user if exists and not the sender
        if ($this->conversation->user_id &&
            !($this->senderGuard === 'user' && $this->conversation->user_id == $this->message->messageable_id)) {
            $recipients->push([
                'type' => 'user',
                'id' => $this->conversation->user_id
            ]);
        }

        // Add vendor if exists and not the sender
        if ($this->conversation->vendor_id &&
            !($this->senderGuard === 'vendor' && $this->conversation->vendor_id == $this->message->messageable_id)) {
            $recipients->push([
                'type' => 'vendor',
                'id' => $this->conversation->vendor_id
            ]);
        }

        // Add admin if exists and not the sender
        if ($this->conversation->admin_id &&
            !($this->senderGuard === 'admin' && $this->conversation->admin_id == $this->message->messageable_id)) {
            $recipients->push([
                'type' => 'admin',
                'id' => $this->conversation->admin_id
            ]);
        }

        // Add courier if exists and not the sender
        if ($this->conversation->couier_id &&
            !($this->senderGuard === 'courier' && $this->conversation->couier_id == $this->message->messageable_id)) {
            $recipients->push([
                'type' => 'courier',
                'id' => $this->conversation->couier_id
            ]);
        }

        return $recipients;
    }

    /**
     * Get FCM device tokens for a recipient
     */
    private function getRecipientDeviceTokens(array $recipientData): \Illuminate\Support\Collection
    {
        $type = $recipientData['type'];
        $id = $recipientData['id'];

        // Get the model based on type
        $model = match($type) {
            'user' => \Modules\User\Models\User::find($id),
            'vendor' => \Modules\Vendor\Models\Vendor::find($id),
            'admin' => \Modules\Admin\Models\Admin::find($id),
            'courier' => \Modules\Couier\Models\Couier::find($id),
            default => null
        };

        if (!$model) {
            return collect();
        }

        // Get active FCM tokens
        return $model->tokens()
            ->where('notification_active', true)
            ->whereNotNull('fcm_device')
            ->get();
    }
}
