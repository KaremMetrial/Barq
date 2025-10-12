<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->get();
        $stores = DB::table('stores')->get();
        $products = DB::table('products')->get();
        $coupons = DB::table('coupons')->get();

        $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'on_the_way', 'delivered', 'cancelled'];

        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $store = $stores->random();
                $status = $statuses[array_rand($statuses)];

                $orderId = DB::table('orders')->insertGetId([
                    'order_number' => 'ORD' . str_pad($user->id . $i, 6, '5', STR_PAD_LEFT),
                    'reference_code' => 'REF' . \Illuminate\Support\Str::random(10),
                    'type' => 'service',
                    'status' => $status,
                    'note' => 'ملاحظات خاصة على الطلب',
                    'total_amount' => 0, // Will calculate
                    'discount_amount' => 0,
                    'paid_amount' => 0,
                    'delivery_fee' => 15.000,
                    'tax_amount' => 0,
                    'service_fee' => 0,
                    'payment_status' => 'paid',
                    'otp_code' => rand(1000, 9999),
                    'requires_otp' => true,
                    'delivery_address' => 'عنوان التوصيل للمستخدم',
                    'tip_amount' => rand(0, 10),
                    'estimated_delivery_time' => now()->addMinutes(45),
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                    'coupon_id' => rand(0, 1) ? $coupons->random()->id : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(0, 29)),
                ]);

                // Order items
                $orderProducts = $products->random(rand(1, 4));
                $subtotal = 0;

                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 3);
                    $itemTotal = '100' * $quantity;
                    $subtotal += $itemTotal;

                    $orderItemId = DB::table('order_items')->insertGetId([
                        'quantity' => $quantity,
                        'total_price' => $itemTotal,
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'product_option_value_id' => 1, // Default option
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Update order totals
                $taxAmount = $subtotal * 0.14;
                $serviceFee = $subtotal * 0.05;
                $totalAmount = $subtotal + $taxAmount + $serviceFee + 15.000; // + delivery

                DB::table('orders')->where('id', $orderId)->update([
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'service_fee' => $serviceFee,
                    'paid_amount' => $totalAmount,
                ]);

                // Order status history
                $statusHistory = ['pending', 'confirmed', 'preparing'];
                if ($status === 'delivered') {
                    $statusHistory = array_merge($statusHistory, ['ready', 'on_the_way', 'delivered']);
                }

                $changedAt = now()->subDays(rand(1, 30));
                foreach ($statusHistory as $historyStatus) {
                    DB::table('order_status_histories')->insert([
                        'status' => $historyStatus,
                        'changed_at' => $changedAt,
                        'note' => 'تم تغيير حالة الطلب',
                        'order_id' => $orderId,
                        'created_at' => $changedAt,
                        'updated_at' => $changedAt,
                    ]);
                    $changedAt = $changedAt->addMinutes(rand(10, 60));
                }
            }
        }
    }
}
