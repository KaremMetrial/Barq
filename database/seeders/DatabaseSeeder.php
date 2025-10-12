<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            CountrySeeder::class,
            GovernorateSeeder::class,
            CitySeeder::class,
            ZoneSeeder::class,
            CategorySeeder::class,
            SectionSeeder::class,
            StoreSeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
            UserSeeder::class,
            CouponSeeder::class,
            OptionSeeder::class,
            AddOnSeeder::class,
            CartSeeder::class,
            OrderSeeder::class,
            BannerSeeder::class,
            AdSeeder::class,
            ReviewSeeder::class,
            FavouriteSeeder::class,
        ]);

    }
}
