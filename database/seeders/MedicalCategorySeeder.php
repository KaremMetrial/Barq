<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MedicalCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = [
            'slug' => 'medical',
            'icon' => 'uploads/icons/medical.png',
            'name_ar' => 'طبي',
            'name_en' => 'Medical',
            'is_active' => true,
            'sort_order' => 4,
            'is_featured' => false,
        ];

        $catId = DB::table('categories')->insertGetId([
            'slug' => $category['slug'],
            'icon' => $category['icon'],
            'is_active' => $category['is_active'],
            'sort_order' => $category['sort_order'],
            'is_featured' => $category['is_featured'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_translations')->insert([
            [
                'name' => $category['name_ar'],
                'locale' => 'ar',
                'category_id' => $catId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => $category['name_en'],
                'locale' => 'en',
                'category_id' => $catId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
