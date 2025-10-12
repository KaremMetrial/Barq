<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GovernorateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $egyptId = DB::table('countries')->where('code', 'EG')->first()->id;

        $governorates = [
            ['name_ar' => 'القاهرة', 'name_en' => 'Cairo'],
            ['name_ar' => 'الجيزة', 'name_en' => 'Giza'],
            ['name_ar' => 'الإسكندرية', 'name_en' => 'Alexandria'],
        ];

        foreach ($governorates as $gov) {
            $govId = DB::table('governorates')->insertGetId([
                'country_id' => $egyptId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('governorate_translations')->insert([
                [
                    'locale' => 'ar',
                    'name' => $gov['name_ar'],
                    'governorate_id' => $govId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'locale' => 'en',
                    'name' => $gov['name_en'],
                    'governorate_id' => $govId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
