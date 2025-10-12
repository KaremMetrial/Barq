<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurantSectionId = DB::table('sections')->where('slug', 'restaurants')->first()->id;
        $zoneId = DB::table('zones')->first()->id;

        $stores = [
            [
                'name_ar' => 'ماكدونالدز',
                'name_en' => 'McDonald\'s',
                'phone' => '1234567890',
                'status' => 'approved',
                'logo' => 'uploads/stores/mcdonalds.png',
                'cover_image' => 'uploads/stores/mcdonalds-cover.jpg',
                'message' => 'وجبات سريعة ولذيذة',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.2,
            ],
            [
                'name_ar' => 'بيتزا هت',
                'name_en' => 'Pizza Hut',
                'phone' => '1234567891',
                'status' => 'approved',
                'logo' => 'uploads/stores/pizzahut.png',
                'cover_image' => 'uploads/stores/pizzahut-cover.jpg',
                'message' => 'أفضل البيتزا في المدينة',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.5,
            ]
        ];

        foreach ($stores as $store) {
            $storeId = DB::table('stores')->insertGetId([
                'status' => $store['status'],
                'logo' => $store['logo'],
                'cover_image' => $store['cover_image'],
                'phone' => $store['phone'],
                'message' => $store['message'],
                'is_featured' => $store['is_featured'],
                'is_active' => $store['is_active'],
                'is_closed' => $store['is_closed'],
                'avg_rate' => $store['avg_rate'],
                'section_id' => $restaurantSectionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Store translations
            DB::table('store_translations')->insert([
                [
                    'name' => $store['name_ar'],
                    'locale' => 'ar',
                    'store_id' => $storeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => $store['name_en'],
                    'locale' => 'en',
                    'store_id' => $storeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            // Store settings
            DB::table('store_settings')->insert([
                'store_id' => $storeId,
                'orders_enabled' => true,
                'delivery_service_enabled' => true,
                'external_pickup_enabled' => true,
                'self_delivery_enabled' => false,
                'free_delivery_enabled' => false,
                'minimum_order_amount' => 50.000,
                'delivery_time_min' => 30,
                'delivery_time_max' => 45,
                'delivery_type_unit' => 'minute',
                'tax_rate' => 14.000,
                'service_fee_percentage' => 5.000,
                'order_interval_time' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Working days
            $days = [
                ['day_of_week' => 1, 'open_time' => '08:00', 'close_time' => '23:00'],
                ['day_of_week' => 2, 'open_time' => '08:00', 'close_time' => '23:00'],
                ['day_of_week' => 3, 'open_time' => '08:00', 'close_time' => '23:00'],
                ['day_of_week' => 4, 'open_time' => '08:00', 'close_time' => '23:00'],
                ['day_of_week' => 5, 'open_time' => '08:00', 'close_time' => '00:00'],
                ['day_of_week' => 6, 'open_time' => '09:00', 'close_time' => '00:00'],
                ['day_of_week' => 0, 'open_time' => '09:00', 'close_time' => '23:00'],
            ];

            foreach ($days as $day) {
                DB::table('working_days')->insert([
                    'store_id' => $storeId,
                    'day_of_week' => $day['day_of_week'],
                    'open_time' => $day['open_time'],
                    'close_time' => $day['close_time'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Store address
            DB::table('addresses')->insert([
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'name' => $store['name_ar'],
                'phone' => $store['phone'],
                'is_default' => true,
                'type' => 'work',
                'addressable_type' => 'App\\Models\\Store',
                'addressable_id' => $storeId,
                'zone_id' => $zoneId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
