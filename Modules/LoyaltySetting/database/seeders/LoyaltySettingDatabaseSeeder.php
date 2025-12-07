<?php

namespace Modules\LoyaltySetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LoyaltySetting\Models\LoyaltySetting;
use Modules\LoyaltySetting\Models\LoyaltyTransaction;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use App\Enums\LoyaltyTrransactionTypeEnum;

class LoyaltySettingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Loyalty Settings...');

        // Get all countries
        $countries = Country::all();

        if ($countries->isEmpty()) {
            $this->command->warn('No countries found. Please seed countries first.');
            return;
        }

        // Create loyalty settings for each country
        foreach ($countries as $country) {
            LoyaltySetting::updateOrCreate(
                ['country_id' => $country->id],
                [
                    'earn_rate' => $this->getEarnRateForCountry($country->code),
                    'min_order_for_earn' => $this->getMinOrderForCountry($country->code),
                    'referral_points' => $this->getReferralPointsForCountry($country->code),
                    'rating_points' => 30, // Standard across all countries
                ]
            );
        }

        $this->command->info('Loyalty Settings seeded successfully!');

        // Seed sample loyalty transactions if users exist
        $this->seedSampleTransactions();
    }

    /**
     * Get earn rate based on country
     */
    private function getEarnRateForCountry(string $countryCode): float
    {
        return match (strtoupper($countryCode)) {
            'US', 'CA', 'GB', 'AU' => 0.10, // 10% earn rate for high-value markets
            'AE', 'SA', 'KW', 'QA' => 0.08, // 8% for Gulf countries
            'EG', 'JO', 'LB' => 0.12, // 12% for MENA region
            'IN', 'PK', 'BD' => 0.15, // 15% for South Asia
            default => 0.10, // Default 10%
        };
    }

    /**
     * Get minimum order amount for earning points
     */
    private function getMinOrderForCountry(string $countryCode): float
    {
        return match (strtoupper($countryCode)) {
            'US', 'CA', 'GB', 'AU' => 20.00,
            'AE', 'SA', 'KW', 'QA' => 50.00,
            'EG', 'JO', 'LB' => 100.00,
            'IN', 'PK', 'BD' => 500.00,
            default => 10.00,
        };
    }

    /**
     * Get referral points based on country
     */
    private function getReferralPointsForCountry(string $countryCode): int
    {
        return match (strtoupper($countryCode)) {
            'US', 'CA', 'GB', 'AU' => 500,
            'AE', 'SA', 'KW', 'QA' => 300,
            'EG', 'JO', 'LB' => 200,
            'IN', 'PK', 'BD' => 150,
            default => 200,
        };
    }

    /**
     * Seed sample loyalty transactions for testing
     */
    private function seedSampleTransactions(): void
    {
        $users = User::limit(10)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping sample transactions.');
            return;
        }

        $this->command->info('Seeding sample loyalty transactions...');

        foreach ($users as $user) {
            $currentBalance = 0;

            // Create 3-5 random transactions per user
            $transactionCount = rand(3, 5);

            for ($i = 0; $i < $transactionCount; $i++) {
                $type = $this->getRandomTransactionType();
                $points = $this->getPointsForType($type);

                // Update balance
                $currentBalance += $points;
                if ($currentBalance < 0) {
                    $currentBalance = 0;
                }

                LoyaltyTransaction::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'points' => $points,
                    'points_balance_after' => $currentBalance,
                    'description' => $this->getDescriptionForType($type),
                    'referenceable_type' => User::class,
                    'referenceable_id' => $user->id,
                    'expires_at' => $type === LoyaltyTrransactionTypeEnum::EARNED
                        ? now()->addYear()
                        : null,
                    'created_at' => now()->subDays(rand(1, 90)),
                ]);
            }

            // Update user's loyalty points to match final balance
            $user->update(['loyalty_points' => $currentBalance]);
        }

        $this->command->info('Sample loyalty transactions seeded successfully!');
    }

    /**
     * Get random transaction type
     */
    private function getRandomTransactionType(): LoyaltyTrransactionTypeEnum
    {
        $types = [
            LoyaltyTrransactionTypeEnum::EARNED,
            LoyaltyTrransactionTypeEnum::EARNED,
            LoyaltyTrransactionTypeEnum::EARNED, // More earned transactions
            LoyaltyTrransactionTypeEnum::REDEEMED,
            LoyaltyTrransactionTypeEnum::ADJUSTED,
        ];

        return $types[array_rand($types)];
    }

    /**
     * Get points amount based on transaction type
     */
    private function getPointsForType(LoyaltyTrransactionTypeEnum $type): float
    {
        return match ($type) {
            LoyaltyTrransactionTypeEnum::EARNED => rand(10, 500),
            LoyaltyTrransactionTypeEnum::REDEEMED => -rand(50, 300),
            LoyaltyTrransactionTypeEnum::ADJUSTED => rand(-100, 100),
            LoyaltyTrransactionTypeEnum::EXPIRED => -rand(10, 100),
        };
    }

    /**
     * Get description based on transaction type
     */
    private function getDescriptionForType(LoyaltyTrransactionTypeEnum $type): string
    {
        return match ($type) {
            LoyaltyTrransactionTypeEnum::EARNED => collect([
                'Points earned from order #' . rand(1000, 9999),
                'Referral bonus points',
                'Rating reward points',
                'Welcome bonus points',
                'Special promotion points',
            ])->random(),
            LoyaltyTrransactionTypeEnum::REDEEMED => collect([
                'Redeemed for wallet credit',
                'Redeemed for discount coupon',
                'Redeemed for free delivery',
            ])->random(),
            LoyaltyTrransactionTypeEnum::ADJUSTED => collect([
                'Manual adjustment by admin',
                'Compensation points',
                'Correction adjustment',
            ])->random(),
            LoyaltyTrransactionTypeEnum::EXPIRED => 'Points expired',
        };
    }
}
