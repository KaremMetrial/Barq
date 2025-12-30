<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Couier\Models\Couier;
use Modules\Couier\Services\CourierLocationCacheService;

class WarmCourierLocationCache extends Command
{
    protected $signature = 'cache:warm-courier-locations';
    protected $description = 'Warm courier location cache with active couriers';

    public function handle()
    {
        $this->info('Warming courier location cache...');

        $activeCouriers = Couier::where('status', 'active')
            ->where('avaliable_status', 'available')
            ->whereHas('shifts', function ($query) {
                $query->where('is_open', true)->whereNull('end_time');
            })
            ->get();

        $cacheService = app(CourierLocationCacheService::class);
        $count = 0;

        foreach ($activeCouriers as $courier) {
            // This would ideally get last known location from database
            // For now, just initialize cache structure
            $cacheService->initializeCourierInCache($courier->id);
            $count++;
        }

        $this->info("Warmed cache for {$count} active couriers");
        return self::SUCCESS;
    }
}
