<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Couier\Models\CouierShift;
use Modules\Couier\Services\CourierShiftService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCloseExpiredShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:auto-close-expired
                            {--hours=2 : Hours after expected end time to auto-close}
                            {--dry-run : Show what would be closed without actually closing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close courier shifts that have exceeded their expected end time';

    protected CourierShiftService $shiftService;

    public function __construct(CourierShiftService $shiftService)
    {
        parent::__construct();
        $this->shiftService = $shiftService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $graceHours = (int) $this->option('hours');
        $isDryRun = $this->option('dry-run');

        $this->info("🔍 Finding expired courier shifts (grace period: {$graceHours} hours)...");

        // Find expired shifts
        $expiredShifts = $this->findExpiredShifts($graceHours);

        if ($expiredShifts->isEmpty()) {
            $this->info('✅ No expired shifts found.');
            return self::SUCCESS;
        }

        $this->info("📋 Found {$expiredShifts->count()} expired shifts to process.");

        if ($isDryRun) {
            $this->displayExpiredShifts($expiredShifts, $graceHours);
            $this->warn('🔸 Dry run mode - no shifts were actually closed.');
            return self::SUCCESS;
        }

        // Process the shifts
        $results = $this->processExpiredShifts($expiredShifts, $graceHours);

        // Display results
        $this->displayResults($results);

        return self::SUCCESS;
    }

    /**
     * Find shifts that have expired
     */
    private function findExpiredShifts(int $graceHours)
    {
        $cutoffTime = now()->subHours($graceHours);

        return CouierShift::where('is_open', true)
            ->whereNotNull('expected_end_time')
            ->where('expected_end_time', '<', $cutoffTime)
            ->with(['couier', 'shiftTemplate'])
            ->orderBy('expected_end_time', 'asc')
            ->get();
    }

    /**
     * Display expired shifts for dry run
     */
    private function displayExpiredShifts($expiredShifts, int $graceHours)
    {
        $this->table(
            ['ID', 'Courier', 'Template', 'Expected End', 'Overtime (hrs)', 'Started At'],
            $expiredShifts->map(function ($shift) use ($graceHours) {
                $overtimeHours = now()->diffInHours($shift->expected_end_time);

                return [
                    $shift->id,
                    $shift->couier->first_name . ' ' . $shift->couier->last_name,
                    $shift->shiftTemplate?->name ?? 'N/A',
                    $shift->expected_end_time->format('Y-m-d H:i'),
                    $overtimeHours,
                    $shift->start_time?->format('Y-m-d H:i') ?? 'Not started'
                ];
            })->toArray()
        );
    }

    /**
     * Process expired shifts
     */
    private function processExpiredShifts($expiredShifts, int $graceHours): array
    {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $progressBar = $this->output->createProgressBar($expiredShifts->count());
        $progressBar->start();

        foreach ($expiredShifts as $shift) {
            try {
                // Auto-close the shift
                $this->autoCloseShift($shift, $graceHours);

                $results['successful']++;

                Log::info('Auto-closed expired shift', [
                    'shift_id' => $shift->id,
                    'courier_id' => $shift->couier_id,
                    'expected_end' => $shift->expected_end_time,
                    'actual_close' => now(),
                    'grace_hours' => $graceHours
                ]);

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'shift_id' => $shift->id,
                    'error' => $e->getMessage()
                ];

                Log::error('Failed to auto-close shift', [
                    'shift_id' => $shift->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Auto-close a single shift
     */
    private function autoCloseShift(CouierShift $shift, int $graceHours): void
    {
        // Update shift with auto-close data
        $shift->update([
            'end_time' => now(),
            'is_open' => false,
            'notes' => ($shift->notes ? $shift->notes . ' | ' : '') .
                      "Auto-closed after {$graceHours} hours grace period. " .
                      "Expected end: {$shift->expected_end_time->format('Y-m-d H:i')}"
        ]);

        // Update related data if needed (orders, earnings, etc.)
        $this->updateShiftRelatedData($shift);
    }

    /**
     * Update related data when auto-closing shift
     */
    private function updateShiftRelatedData(CouierShift $shift): void
    {
        // Here you could update:
        // - Calculate final earnings
        // - Update order statuses
        // - Send notifications
        // - Update performance metrics

        // For now, just log the action
        $this->info("Auto-closed shift #{$shift->id} for courier {$shift->couier->first_name}");
    }

    /**
     * Display processing results
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Processing Results:');
        $this->info("✅ Successfully closed: {$results['successful']} shifts");
        $this->info("❌ Failed to close: {$results['failed']} shifts");

        if (!empty($results['errors'])) {
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  Shift #{$error['shift_id']}: {$error['error']}");
            }
        }
    }
}
