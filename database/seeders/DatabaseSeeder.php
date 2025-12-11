<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Admin\Models\Admin;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456789'),
            'phone' => '0123456789',
        ]);
        $this->call([
            RolePermissionSeeder::class,
            SettingSeeder::class,
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
            CouierSeeder::class,
            ShiftSeeder::class,
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
