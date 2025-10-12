<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->get();
        $products = DB::table('products')->get();

        foreach ($users as $user) {
            $cartId = DB::table('carts')->insertGetId([
                'cart_key' => \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'is_group_order' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add 2-3 random products to cart
            $cartProducts = $products->random(rand(2, 3));

            foreach ($cartProducts as $product) {
                DB::table('cart_items')->insert([
                    'quantity' => rand(1, 3),
                    'total_price' => '100' * rand(1, 3),
                    'note' => 'ملاحظات خاصة على الطلب',
                    'cart_id' => $cartId,
                    'product_id' => $product->id,
                    'added_by_user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
