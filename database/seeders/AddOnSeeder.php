<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddOnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addOns = [
            [
                'price' => 5.000,
                'name_ar' => 'جبنة إضافية',
                'name_en' => 'Extra Cheese',
                'description_ar' => 'طبقة إضافية من الجبنة',
                'description_en' => 'Extra layer of cheese',
                'is_active' => true,
            ],
            [
                'price' => 3.000,
                'name_ar' => 'مايونيز',
                'name_en' => 'Mayonnaise',
                'description_ar' => 'صلصة مايونيز',
                'description_en' => 'Mayonnaise sauce',
                'is_active' => true,
            ],
            [
                'price' => 2.000,
                'name_ar' => 'كاتشب',
                'name_en' => 'Ketchup',
                'description_ar' => 'صلصة كاتشب',
                'description_en' => 'Ketchup sauce',
                'is_active' => true,
            ]
        ];

        foreach ($addOns as $addOn) {
            $addOnId = DB::table('add_ons')->insertGetId([
                'price' => $addOn['price'],
                'is_active' => $addOn['is_active'],
                'applicable_to' => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('add_on_translations')->insert([
                [
                    'name' => $addOn['name_ar'],
                    'description' => $addOn['description_ar'],
                    'locale' => 'ar',
                    'add_on_id' => $addOnId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => $addOn['name_en'],
                    'description' => $addOn['description_en'],
                    'locale' => 'en',
                    'add_on_id' => $addOnId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
