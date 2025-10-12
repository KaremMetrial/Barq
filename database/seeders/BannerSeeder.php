<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = DB::table('cities')->get();

        $banners = [
            [
                'title_ar' => 'عروض خاصة',
                'title_en' => 'Special Offers',
                'image' => 'uploads/banners/banner1.jpg',
                'link' => '/offers',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(30),
                'is_active' => true,
            ],
            [
                'title_ar' => 'توصيل مجاني',
                'title_en' => 'Free Delivery',
                'image' => 'uploads/banners/banner2.jpg',
                'link' => '/free-delivery',
                'start_date' => now(),
                'end_date' => now()->addDays(15),
                'is_active' => true,
            ]
        ];

        foreach ($banners as $banner) {
            $bannerId = DB::table('banners')->insertGetId([
                'image' => $banner['image'],
                'link' => $banner['link'],
                'start_date' => $banner['start_date'],
                'end_date' => $banner['end_date'],
                'is_active' => $banner['is_active'],
                'bannerable_type' => 'App\\Models\\Store',
                'bannerable_id' => 1,
                'city_id' => $cities->first()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('banner_translations')->insert([
                [
                    'title' => $banner['title_ar'],
                    'locale' => 'ar',
                    'banner_id' => $bannerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => $banner['title_en'],
                    'locale' => 'en',
                    'banner_id' => $bannerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
