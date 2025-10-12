<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nasrCityId = DB::table('city_translations')
                      ->where('name', 'Nasr City')
                      ->first()
                      ->city_id;

        $zones = [
            ['name_ar' => 'منطقة أ', 'name_en' => 'Zone A'],
            ['name_ar' => 'منطقة ب', 'name_en' => 'Zone B'],
        ];

        foreach ($zones as $zone) {
            $zoneId = DB::table('zones')->insertGetId([
                'city_id' => $nasrCityId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('zone_translations')->insert([
                [
                    'locale' => 'ar',
                    'name' => $zone['name_ar'],
                    'zone_id' => $zoneId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'locale' => 'en',
                    'name' => $zone['name_en'],
                    'zone_id' => $zoneId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
