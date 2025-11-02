<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpireLoyaltyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:expire-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire loyalty points that have reached their expiry date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting loyalty points expiration process...');

        $loyaltyService = app(\Modules\User\Services\LoyaltyService::class);
        $expiredCount = $loyaltyService->expireOldPoints();

        $this->info("Expired {$expiredCount} loyalty point transactions.");
        $this->info('Loyalty points expiration process completed.');
    }
}
