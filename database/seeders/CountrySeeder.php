<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => 'EG',
                'currency_symbol' => 'EGP',
                'flag' => 'uploads/flags/eg.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SA',
                'currency_symbol' => 'SAR',
                'flag' => 'uploads/flags/sa.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($countries as $country) {
            $countryId = DB::table('countries')->insertGetId($country);

            DB::table('country_translations')->insert([
                [
                    'locale' => 'ar',
                    'name' => $country['code'] == 'EG' ? 'مصر' : 'السعودية',
                    'country_id' => $countryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'locale' => 'en',
                    'name' => $country['code'] == 'EG' ? 'Egypt' : 'Saudi Arabia',
                    'country_id' => $countryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
