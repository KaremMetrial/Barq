<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'English',
                'code' => 'en',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_default' => false,
                'is_active' => true,
            ]
        ];

        foreach ($languages as $language) {
            DB::table('languages')->insert($language);
        }
    }
}
