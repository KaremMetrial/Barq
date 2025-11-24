<?php
namespace App\Jobs;

use App\Services\FirebaseService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFcmNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $deviceToken;
    public string $title;
    public string $body;
    public array $data;

    /**
     * Create a new job instance.
     */
    public function __construct($deviceToken, string $title, string $body, array $data = [])
    {
        $this->deviceToken = $deviceToken;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }


    /**
     * Execute the job.
     */
    public function handle(FirebaseService $fcmService): void
    {
        $fcmService->sendToDevice(
            $this->deviceToken,
            $this->title,
            $this->body,
            $this->data
        );
    }
}
