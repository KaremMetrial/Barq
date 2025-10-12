<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cairoId = DB::table('governorate_translations')
                    ->where('name', 'Cairo')
                    ->first()
                    ->governorate_id;

        $cities = [
            ['name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City'],
            ['name_ar' => 'المعادي', 'name_en' => 'Maadi'],
            ['name_ar' => 'التجمع الخامس', 'name_en' => 'Fifth Settlement'],
        ];

        foreach ($cities as $city) {
            $cityId = DB::table('cities')->insertGetId([
                'governorate_id' => $cairoId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('city_translations')->insert([
                [
                    'locale' => 'ar',
                    'name' => $city['name_ar'],
                    'city_id' => $cityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'locale' => 'en',
                    'name' => $city['name_en'],
                    'city_id' => $cityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
