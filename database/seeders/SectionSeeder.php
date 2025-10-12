<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [
            [
                'slug' => 'restaurants',
                'icon' => 'uploads/icons/restaurant.png',
                'name_ar' => 'مطاعم',
                'name_en' => 'Restaurants',
                'is_restaurant' => true,
                'is_active' => true,
                'type' => 'restaurant',
            ],
            [
                'slug' => 'cafes',
                'icon' => 'uploads/icons/cafe.png',
                'name_ar' => 'كافيهات',
                'name_en' => 'Cafes',
                'is_restaurant' => false,
                'is_active' => true,
                'type' => 'cafe',
            ],
            [
                'slug' => 'pharmacies',
                'icon' => 'uploads/icons/pharmacy.png',
                'name_ar' => 'صيدليات',
                'name_en' => 'Pharmacies',
                'is_restaurant' => false,
                'is_active' => true,
                'type' => 'pharmacy',
            ]
        ];

        foreach ($sections as $section) {
            $sectionId = DB::table('sections')->insertGetId([
                'slug' => $section['slug'],
                'icon' => $section['icon'],
                'is_restaurant' => $section['is_restaurant'],
                'is_active' => $section['is_active'],
                'type' => $section['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('section_translations')->insert([
                [
                    'name' => $section['name_ar'],
                    'locale' => 'ar',
                    'section_id' => $sectionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => $section['name_en'],
                    'locale' => 'en',
                    'section_id' => $sectionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
