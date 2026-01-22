<?php

namespace Modules\Couier\Console;

use Illuminate\Console\Command;
use Modules\Couier\Models\CouierShift;
use Modules\Couier\Services\CourierShiftService;
use Carbon\Carbon;

class CloseOverdueShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couier:close-overdue-shifts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close courier shifts that have exceeded their expected end time.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(protected CourierShiftService $courierShiftService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Checking for overdue courier shifts...');

        // Find all open shifts where expected_end_time is in the past
        $overdueShifts = CouierShift::query()
            ->where('is_open', true)
            ->where('expected_end_time', '<', Carbon::now())
            ->get();

        if ($overdueShifts->isEmpty()) {
            $this->info('No overdue shifts found.');
            return 0;
        }

        $count = 0;
        foreach ($overdueShifts as $shift) {
            try {
                $this->courierShiftService->forceCloseShift($shift->id);
                $this->info("Closed shift ID: {$shift->id} for Courier ID: {$shift->couier_id}");
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to close shift ID: {$shift->id}. Error: " . $e->getMessage());
            }
        }

        $this->info("Successfully closed {$count} overdue shifts.");
        return 0;
    }
}
