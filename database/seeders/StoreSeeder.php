<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\SectionTypeEnum;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // Get restaurant and pharmacy sections
        $restaurantSection = DB::table('sections')
            ->where('type', SectionTypeEnum::RESTAURANT->value)
            ->first();

        $pharmacySection = DB::table('sections')
            ->where('type', SectionTypeEnum::PHARMACY->value)
            ->first();

        if (!$restaurantSection) {
            throw new \Exception("Section with type '".SectionTypeEnum::RESTAURANT->value."' not found. Please seed the sections table first.");
        }

        if (!$pharmacySection) {
            throw new \Exception("Section with type '".SectionTypeEnum::PHARMACY->value."' not found. Please seed the sections table first.");
        }

        // Get zones
        $zones = DB::table('zones')->get();
        if ($zones->isEmpty()) {
            throw new \Exception("No zones found. Please seed the zones table first.");
        }

        $restaurantSectionId = $restaurantSection->id;
        $pharmacySectionId = $pharmacySection->id;

        // Restaurant stores data
        $restaurantStores = [
            [
                'name_ar' => 'ماكدونالدز',
                'name_en' => 'McDonald\'s',
                'phone' => '1000000001',
                'status' => 'approved',
                'logo' => 'uploads/stores/mcdonalds.png',
                'cover_image' => 'uploads/stores/mcdonalds-cover.jpg',
                'message' => 'وجبات سريعة ولذيذة',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.2,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
            ],
            [
                'name_ar' => 'بيتزا هت',
                'name_en' => 'Pizza Hut',
                'phone' => '1000000002',
                'status' => 'approved',
                'logo' => 'uploads/stores/pizzahut.png',
                'cover_image' => 'uploads/stores/pizzahut-cover.jpg',
                'message' => 'أفضل البيتزا في المدينة',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.5,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0450,
                'longitude' => 31.2360,
            ],
            [
                'name_ar' => 'كنتاكي',
                'name_en' => 'KFC',
                'phone' => '1000000003',
                'status' => 'approved',
                'logo' => 'uploads/stores/kfc.png',
                'cover_image' => 'uploads/stores/kfc-cover.jpg',
                'message' => 'دجاج مقرمش ولذيذ',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.1,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0460,
                'longitude' => 31.2370,
            ],
            [
                'name_ar' => 'ستاربكس',
                'name_en' => 'Starbucks',
                'phone' => '1000000004',
                'status' => 'approved',
                'logo' => 'uploads/stores/starbucks.png',
                'cover_image' => 'uploads/stores/starbucks-cover.jpg',
                'message' => 'قهوة ممتازة ومشروبات متنوعة',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.3,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0470,
                'longitude' => 31.2380,
            ],
            [
                'name_ar' => 'برجر كنج',
                'name_en' => 'Burger King',
                'phone' => '1000000005',
                'status' => 'approved',
                'logo' => 'uploads/stores/burgerking.png',
                'cover_image' => 'uploads/stores/burgerking-cover.jpg',
                'message' => 'برجر شهير ولذيذ',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.0,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0480,
                'longitude' => 31.2390,
            ],
            [
                'name_ar' => 'دومينوز بيتزا',
                'name_en' => 'Domino\'s Pizza',
                'phone' => '1000000006',
                'status' => 'approved',
                'logo' => 'uploads/stores/dominos.png',
                'cover_image' => 'uploads/stores/dominos-cover.jpg',
                'message' => 'بيتزا طازجة وسريعة',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.4,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0490,
                'longitude' => 31.2400,
            ],
            [
                'name_ar' => 'هارديز',
                'name_en' => 'Hardee\'s',
                'phone' => '1000000007',
                'status' => 'approved',
                'logo' => 'uploads/stores/hardees.png',
                'cover_image' => 'uploads/stores/hardees-cover.jpg',
                'message' => 'برجر أمريكي أصيل',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 3.9,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0500,
                'longitude' => 31.2410,
            ],
            [
                'name_ar' => 'تشيكن فيلا',
                'name_en' => 'Chicken Villa',
                'phone' => '1000000008',
                'status' => 'approved',
                'logo' => 'uploads/stores/chickenvilla.png',
                'cover_image' => 'uploads/stores/chickenvilla-cover.jpg',
                'message' => 'دجاج مشوي ولذيذ',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.2,
                'section_id' => $restaurantSectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0510,
                'longitude' => 31.2420,
            ],
        ];

        // Pharmacy stores data
        $pharmacyStores = [
            [
                'name_ar' => 'صيدلية النهدة',
                'name_en' => 'Al Nahda Pharmacy',
                'phone' => '2000000001',
                'status' => 'approved',
                'logo' => 'uploads/stores/alnahda.png',
                'cover_image' => 'uploads/stores/alnahda-cover.jpg',
                'message' => 'خدمة طبية موثوقة',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.6,
                'section_id' => $pharmacySectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0520,
                'longitude' => 31.2430,
            ],
            [
                'name_ar' => 'صيدلية الرياض',
                'name_en' => 'Al Riyadh Pharmacy',
                'phone' => '2000000002',
                'status' => 'approved',
                'logo' => 'uploads/stores/alriyadh.png',
                'cover_image' => 'uploads/stores/alriyadh-cover.jpg',
                'message' => 'أدوية ومستلزمات طبية',
                'is_featured' => true,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.4,
                'section_id' => $pharmacySectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0530,
                'longitude' => 31.2440,
            ],
            [
                'name_ar' => 'صيدلية الشفاء',
                'name_en' => 'Al Shifa Pharmacy',
                'phone' => '2000000003',
                'status' => 'approved',
                'logo' => 'uploads/stores/alshifa.png',
                'cover_image' => 'uploads/stores/alshifa-cover.jpg',
                'message' => 'رعاية صحية شاملة',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.3,
                'section_id' => $pharmacySectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0540,
                'longitude' => 31.2450,
            ],
            [
                'name_ar' => 'صيدلية الامل',
                'name_en' => 'Al Amal Pharmacy',
                'phone' => '2000000004',
                'status' => 'approved',
                'logo' => 'uploads/stores/alamal.png',
                'cover_image' => 'uploads/stores/alamal-cover.jpg',
                'message' => 'خدمات طبية متميزة',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.1,
                'section_id' => $pharmacySectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0550,
                'longitude' => 31.2460,
            ],
            [
                'name_ar' => 'صيدلية الحياة',
                'name_en' => 'Al Hayat Pharmacy',
                'phone' => '2000000005',
                'status' => 'approved',
                'logo' => 'uploads/stores/alhayat.png',
                'cover_image' => 'uploads/stores/alhayat-cover.jpg',
                'message' => 'صحتك أولويتنا',
                'is_featured' => false,
                'is_active' => true,
                'is_closed' => false,
                'avg_rate' => 4.5,
                'section_id' => $pharmacySectionId,
                'zone_id' => $zones->first()->id,
                'latitude' => 30.0560,
                'longitude' => 31.2470,
            ],
        ];

        $allStores = array_merge($restaurantStores, $pharmacyStores);

        foreach ($allStores as $store) {
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
                'section_id' => $store['section_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Translations
            DB::table('store_translations')->insert([
                ['name' => $store['name_ar'], 'locale' => 'ar', 'store_id' => $storeId, 'created_at' => now(), 'updated_at' => now()],
                ['name' => $store['name_en'], 'locale' => 'en', 'store_id' => $storeId, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Settings
            DB::table('store_settings')->insert([
                'store_id' => $storeId,
                'orders_enabled' => true,
                'delivery_service_enabled' => true,
                'external_pickup_enabled' => true,
                'self_delivery_enabled' => false,
                'free_delivery_enabled' => false,
                'minimum_order_amount' => rand(30, 100),
                'delivery_time_min' => rand(20, 40),
                'delivery_time_max' => rand(45, 90),
                'delivery_type_unit' => 'minute',
                'tax_rate' => rand(10, 20),
                'service_fee_percentage' => rand(3, 8),
                'order_interval_time' => rand(5, 15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Working days - vary by store type
            if ($store['section_id'] == $pharmacySectionId) {
                // Pharmacies often open longer hours
                $days = [
                    ['day_of_week' => 1, 'open_time' => '08:00', 'close_time' => '23:00'],
                    ['day_of_week' => 2, 'open_time' => '08:00', 'close_time' => '23:00'],
                    ['day_of_week' => 3, 'open_time' => '08:00', 'close_time' => '23:00'],
                    ['day_of_week' => 4, 'open_time' => '08:00', 'close_time' => '23:00'],
                    ['day_of_week' => 5, 'open_time' => '08:00', 'close_time' => '23:00'],
                    ['day_of_week' => 6, 'open_time' => '09:00', 'close_time' => '23:00'],
                    ['day_of_week' => 0, 'open_time' => '09:00', 'close_time' => '22:00'],
                ];
            } else {
                // Restaurants
                $days = [
                    ['day_of_week' => 1, 'open_time' => '10:00', 'close_time' => '23:00'],
                    ['day_of_week' => 2, 'open_time' => '10:00', 'close_time' => '23:00'],
                    ['day_of_week' => 3, 'open_time' => '10:00', 'close_time' => '23:00'],
                    ['day_of_week' => 4, 'open_time' => '10:00', 'close_time' => '23:00'],
                    ['day_of_week' => 5, 'open_time' => '10:00', 'close_time' => '00:00'],
                    ['day_of_week' => 6, 'open_time' => '11:00', 'close_time' => '00:00'],
                    ['day_of_week' => 0, 'open_time' => '11:00', 'close_time' => '23:00'],
                ];
            }

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

            // Address
            DB::table('addresses')->insert([
                'latitude' => $store['latitude'],
                'longitude' => $store['longitude'],
                'name' => $store['name_ar'],
                'phone' => $store['phone'],
                'is_default' => true,
                'type' => 'work',
                'addressable_type' => 'Modules\\Store\\Models\\Store',
                'addressable_id' => $storeId,
                'zone_id' => $store['zone_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
