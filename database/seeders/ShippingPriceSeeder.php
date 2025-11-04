<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingPrice;

class ShippingPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippingPrices = [
            [
                'zone_id' => 1,
                'base_price' => 5.00,
                'per_km_price' => 1.50,
                'max_price' => 25.00,
                'max_cod_price' => 100.00,
                'enable_cod' => true,
                'vehicle_id' => 1, // Default vehicle
            ],
            [
                'zone_id' => 2,
                'base_price' => 7.00,
                'per_km_price' => 2.00,
                'max_price' => 35.00,
                'max_cod_price' => 150.00,
                'enable_cod' => true,
                'vehicle_id' => 1, // Default vehicle
            ],
            [
                'zone_id' => 3,
                'base_price' => 10.00,
                'per_km_price' => 2.50,
                'max_price' => 50.00,
                'max_cod_price' => 200.00,
                'enable_cod' => true,
                'vehicle_id' => 1, // Default vehicle
            ],
        ];

        foreach ($shippingPrices as $price) {
            ShippingPrice::create($price);
        }

        $this->command->info('Shipping prices seeded successfully!');
    }
}
