<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Couier\Models\Couier;
use Modules\Couier\Models\ShiftTemplate;
use Modules\Couier\Models\CourierShiftTemplate;
use Illuminate\Support\Facades\Hash;
use App\Enums\CouierAvaliableStatusEnum;
use App\Enums\UserStatusEnum;
use Modules\Store\Models\Store;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;

class CouierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get existing stores for assignment
        $stores = Store::all();

        // Generate realistic courier data
        for ($i = 1; $i <= 10; $i++) {
            $status = $faker->randomElement([UserStatusEnum::ACTIVE->value, UserStatusEnum::PENDING->value, UserStatusEnum::INACTIVE->value]);
            $availableStatus = match($status) {
                UserStatusEnum::ACTIVE->value => $faker->randomElement([
                    CouierAvaliableStatusEnum::AVAILABLE->value,
                    CouierAvaliableStatusEnum::BUSY->value,
                    CouierAvaliableStatusEnum::OFF->value
                ]),
                default => CouierAvaliableStatusEnum::OFF->value,
            };

            // Assign store with logic - most active couriers are assigned to active stores
            $storeId = null;
            if ($status === UserStatusEnum::ACTIVE->value && $stores->count() > 0) {
                // 70% chance of being assigned to a store if active and stores exist
                $storeId = $faker->boolean(70) ? $stores->random()->id : null;
            }

            $courier = Couier::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $faker->unique()->email(),
                'password' => Hash::make('password'),
                'phone' => $faker->unique()->phoneNumber(),
                'license_number' => 'C' . $faker->unique()->numberBetween(100000, 999999),
                'avaliable_status' => $availableStatus,
                'avg_rate' => $status === UserStatusEnum::ACTIVE->value ? $faker->randomFloat(1, 3.0, 5.0) : 0.0,
                'status' => $status,
                'store_id' => $storeId,
                'birthday' => $faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'commission_type' => $faker->randomElement(\App\Enums\PlanTypeEnum::values()),
                'commission_amount' => $faker->randomFloat(2, 1, 20),
            ]);

            // Assign shift templates to active couriers
            $this->assignShiftTemplatesToCourier($courier, $faker);
        }
    }

    private function assignShiftTemplatesToCourier(Couier $courier, $faker): void
    {
        if ($courier->status !== UserStatusEnum::ACTIVE->value) {
            return; // Only assign templates to active couriers
        }

        // Get available templates for this courier's store
        $templatesQuery = ShiftTemplate::where('is_active', true);
        if ($courier->store_id) {
            $templatesQuery->where('store_id', $courier->store_id);
        }

        $templates = $templatesQuery->get();

        if ($templates->isEmpty()) {
            return;
        }

        // Assign 1-3 templates per courier randomly
        $templatesToAssign = $templates->random(min(mt_rand(1, 3), $templates->count()));

        foreach ($templatesToAssign as $template) {
            // Skip if already assigned
            $existing = CourierShiftTemplate::where('courier_id', $courier->id)
                ->where('shift_template_id', $template->id)
                ->first();

            if (!$existing) {
                CourierShiftTemplate::create([
                    'courier_id' => $courier->id,
                    'shift_template_id' => $template->id,
                    'notes' => mt_rand(1, 10) > 7 ? 'Auto-assigned by seeder' : null,
                ]);
            }
        }
    }
}
