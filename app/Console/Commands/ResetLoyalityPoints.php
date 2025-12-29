<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetLoyalityPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:reset-points {--confirm : Confirm the reset operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all users loyalty points to zero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('confirm')) {
            $this->error('This command will reset ALL users loyalty points to zero!');
            $this->info('Use --confirm flag to proceed: php artisan loyalty:reset-points --confirm');
            return Command::FAILURE;
        }

        $this->info('Resetting all users loyalty points to zero...');

        $affected = \Modules\User\Models\User::where('loyalty_points', '>', 0)->update(['loyalty_points' => 0]);

        $this->info("Successfully reset loyalty points for {$affected} users.");

        return Command::SUCCESS;
    }
    
}
