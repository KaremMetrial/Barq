<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Vehicle\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            [
                'name' => 'Motorcycle',
                'is_active' => true,
            ],
            [
                'name' => 'Car',
                'is_active' => true,
            ],
            [
                'name' => 'Van',
                'is_active' => true,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }

        $this->command->info('Vehicles seeded successfully!');
    }
}
