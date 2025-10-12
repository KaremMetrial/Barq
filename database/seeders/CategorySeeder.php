<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'burgers',
                'icon' => 'uploads/icons/burger.png',
                'name_ar' => 'برجر',
                'name_en' => 'Burgers',
                'is_active' => true,
                'sort_order' => 1,
                'is_featured' => true,
            ],
            [
                'slug' => 'pizza',
                'icon' => 'uploads/icons/pizza.png',
                'name_ar' => 'بيتزا',
                'name_en' => 'Pizza',
                'is_active' => true,
                'sort_order' => 2,
                'is_featured' => true,
            ],
            [
                'slug' => 'asian',
                'icon' => 'uploads/icons/asian.png',
                'name_ar' => 'مأكولات آسيوية',
                'name_en' => 'Asian Food',
                'is_active' => true,
                'sort_order' => 3,
                'is_featured' => false,
            ]
        ];

        foreach ($categories as $category) {
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
}
