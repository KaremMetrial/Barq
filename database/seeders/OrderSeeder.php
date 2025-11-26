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
        $products = DB::table('products')
            ->join('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->select('products.*', 'product_prices.price')
            ->get();
        $coupons = DB::table('coupons')->get();

        $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'on_the_way', 'delivered', 'cancelled'];

        if ($users->isEmpty() || $stores->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Create 5-10 orders per user for more data
            $orderCount = rand(5, 10);

            for ($i = 0; $i < $orderCount; $i++) {
                $store = $stores->random();
                $status = $statuses[array_rand($statuses)];
                $createdAt = now()->subDays(rand(1, 60))->subHours(rand(1, 24));

                $orderId = DB::table('orders')->insertGetId([
                    'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad($user->id . $i, 6, '0', STR_PAD_LEFT),
                    'reference_code' => strtoupper(\Illuminate\Support\Str::random(10)),
                    'type' => 'service',
                    'status' => $status,
                    'note' => rand(0, 1) ? 'Please deliver to the front door' : null,
                    'total_amount' => 0, // Will calculate
                    'discount_amount' => 0,
                    'paid_amount' => 0,
                    'delivery_fee' => 15.000,
                    'tax_amount' => 0,
                    'service_fee' => 0,
                    'payment_status' => $status === 'cancelled' ? 'failed' : 'paid',
                    'otp_code' => rand(1000, 9999),
                    'requires_otp' => true,
                    'delivery_address_id' => DB::table('addresses')->where('addressable_type', 'App\\Models\\User')->where('addressable_id', $user->id)->first()->id ?? null,
                    'tip_amount' => rand(0, 1) ? rand(5, 20) : 0,
                    'estimated_delivery_time' => $createdAt->copy()->addMinutes(45),
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                    'coupon_id' => ($coupons->isNotEmpty() && rand(0, 3) === 0) ? $coupons->random()->id : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addMinutes(rand(10, 120)),
                ]);

                // Order items
                $orderProducts = $products->where('store_id', $store->id);
                if ($orderProducts->isEmpty()) {
                    $orderProducts = $products->random(rand(1, 3));
                } else {
                    $orderProducts = $orderProducts->random(min($orderProducts->count(), rand(1, 4)));
                }

                $subtotal = 0;

                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 3);
                    $price = $product->price;
                    $itemTotal = $price * $quantity;
                    $subtotal += $itemTotal;

                    DB::table('order_items')->insert([
                        'quantity' => $quantity,
                        'total_price' => $itemTotal,
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'product_option_value_id' => json_encode([]), // Empty JSON array as per schema change
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
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
                    'paid_amount' => $status === 'cancelled' ? 0 : $totalAmount,
                ]);

                // Order status history
                $historyStatuses = ['pending'];
                if ($status !== 'pending') {
                    if ($status === 'cancelled') {
                        $historyStatuses[] = 'cancelled';
                    } else {
                        $historyStatuses[] = 'confirmed';
                        if (in_array($status, ['preparing', 'ready', 'on_the_way', 'delivered'])) $historyStatuses[] = 'preparing';
                        if (in_array($status, ['ready', 'on_the_way', 'delivered'])) $historyStatuses[] = 'ready';
                        if (in_array($status, ['on_the_way', 'delivered'])) $historyStatuses[] = 'on_the_way';
                        if ($status === 'delivered') $historyStatuses[] = 'delivered';
                    }
                }

                $historyTime = $createdAt->copy();
                foreach ($historyStatuses as $historyStatus) {
                    DB::table('order_status_histories')->insert([
                        'status' => $historyStatus,
                        'changed_at' => $historyTime,
                        'note' => 'Status changed to ' . $historyStatus,
                        'order_id' => $orderId,
                        'created_at' => $historyTime,
                        'updated_at' => $historyTime,
                    ]);
                    $historyTime->addMinutes(rand(5, 15));
                }
            }
        }
    }
}
