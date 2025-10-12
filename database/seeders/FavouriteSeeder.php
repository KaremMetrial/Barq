<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FavouriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->get();
        $products = DB::table('products')->get();
        $stores = DB::table('stores')->get();

        foreach ($users as $user) {
            // Add 2-3 products to favourites
            $favProducts = $products->random(rand(2, 3));
            foreach ($favProducts as $product) {
                DB::table('favourites')->insert([
                    'user_id' => $user->id,
                    'favouriteable_type' => 'App\\Models\\Product',
                    'favouriteable_id' => $product->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Add 1-2 stores to favourites
            $favStores = $stores->random(rand(1, 2));
            foreach ($favStores as $store) {
                DB::table('favourites')->insert([
                    'user_id' => $user->id,
                    'favouriteable_type' => 'App\\Models\\Store',
                    'favouriteable_id' => $store->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
