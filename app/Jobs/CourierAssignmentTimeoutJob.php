<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\Couier\Services\SmartOrderAssignmentService;
// use Modules\Couier\Services\SmartOrderAssignmentService;

class CourierAssignmentTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assignmentId;

    /**
     * Create a new job instance.
     */
    public function __construct($assignmentId)
    {
        $this->assignmentId = $assignmentId;
    }

    /**
     * Execute the job.
     */
    public function handle(SmartOrderAssignmentService $assignmentService): void
    {
        $assignmentService->handleTimeout($this->assignmentId);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function retryUntil()
    {
        // Give it one chance, no retries
        return now()->addMinutes(5);
    }
}
