<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->get();
        $stores = DB::table('stores')->get();
        $orders = DB::table('orders')->where('status', 'delivered')->get();

        foreach ($orders as $order) {
            DB::table('reviews')->insert([
                'rating' => rand(3, 5),
                'comment' => 'تجربة رائعة، شكرًا لكم!',
                'food_quality_rating' => rand(3, 5),
                'delivery_speed_rating' => rand(3, 5),
                'order_execution_speed_rating' => rand(3, 5),
                'reviewable_type' => 'App\\Models\\Store',
                'reviewable_id' => $order->store_id,
                'order_id' => $order->id,
                'created_at' => $order->created_at,
                'updated_at' => $order->created_at,
            ]);
        }
    }
}
