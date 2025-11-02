<?php

namespace Database\Seeders;

use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LoyaltyTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing users
        $users = User::take(5)->get();

        if ($users->isEmpty()) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        $transactions = [];

        foreach ($users as $user) {
            // Create earned transactions
            $earnedTransactions = [
                [
                    'user_id' => $user->id,
                    'type' => 'earned',
                    'points' => 150.00,
                    'points_balance_after' => 150.00,
                    'description' => 'Points earned from order #ORD001',
                    'referenceable_type' => 'Modules\\Order\\Models\\Order',
                    'referenceable_id' => 1,
                    'expires_at' => Carbon::now()->addDays(365),
                    'created_at' => Carbon::now()->subDays(30),
                    'updated_at' => Carbon::now()->subDays(30),
                ],
                [
                    'user_id' => $user->id,
                    'type' => 'earned',
                    'points' => 75.50,
                    'points_balance_after' => 225.50,
                    'description' => 'Points earned from order #ORD002',
                    'referenceable_type' => 'Modules\\Order\\Models\\Order',
                    'referenceable_id' => 2,
                    'expires_at' => Carbon::now()->addDays(365),
                    'created_at' => Carbon::now()->subDays(15),
                    'updated_at' => Carbon::now()->subDays(15),
                ],
                [
                    'user_id' => $user->id,
                    'type' => 'earned',
                    'points' => 200.00,
                    'points_balance_after' => 425.50,
                    'description' => 'Points earned from order #ORD003',
                    'referenceable_type' => 'Modules\\Order\\Models\\Order',
                    'referenceable_id' => 3,
                    'expires_at' => Carbon::now()->addDays(365),
                    'created_at' => Carbon::now()->subDays(7),
                    'updated_at' => Carbon::now()->subDays(7),
                ],
            ];

            // Create redeemed transactions
            $redeemedTransactions = [
                [
                    'user_id' => $user->id,
                    'type' => 'redeemed',
                    'points' => -100.00,
                    'points_balance_after' => 325.50,
                    'description' => 'Points redeemed for order discount',
                    'referenceable_type' => 'Modules\\Order\\Models\\Order',
                    'referenceable_id' => 4,
                    'expires_at' => null,
                    'created_at' => Carbon::now()->subDays(5),
                    'updated_at' => Carbon::now()->subDays(5),
                ],
            ];

            // Create expired transactions
            $expiredTransactions = [
                [
                    'user_id' => $user->id,
                    'type' => 'expired',
                    'points' => -50.00,
                    'points_balance_after' => 275.50,
                    'description' => 'Points expired',
                    'referenceable_type' => 'App\\Models\\LoyaltyTransaction',
                    'referenceable_id' => 1,
                    'expires_at' => null,
                    'created_at' => Carbon::now()->subDays(2),
                    'updated_at' => Carbon::now()->subDays(2),
                ],
            ];

            $transactions = array_merge($transactions, $earnedTransactions, $redeemedTransactions, $expiredTransactions);
        }

        foreach ($transactions as $transaction) {
            LoyaltyTransaction::create($transaction);
        }

        $this->command->info('Loyalty transactions seeded successfully for ' . $users->count() . ' users.');
    }
}
