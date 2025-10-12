<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            [
                'input_type' => 'single',
                'is_food_option' => true,
                'name_ar' => 'الحجم',
                'name_en' => 'Size',
            ],
            [
                'input_type' => 'multiple',
                'is_food_option' => true,
                'name_ar' => 'الإضافات',
                'name_en' => 'Add-ons',
            ],
            [
                'input_type' => 'single',
                'is_food_option' => false,
                'name_ar' => 'درجة الحرارة',
                'name_en' => 'Temperature',
            ]
        ];

        foreach ($options as $option) {
            $optionId = DB::table('options')->insertGetId([
                'input_type' => $option['input_type'],
                'is_food_option' => $option['is_food_option'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('option_translations')->insert([
                [
                    'name' => $option['name_ar'],
                    'locale' => 'ar',
                    'option_id' => $optionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => $option['name_en'],
                    'locale' => 'en',
                    'option_id' => $optionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
