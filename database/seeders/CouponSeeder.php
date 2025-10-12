<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name_ar' => 'خصم ترحيبي',
                'name_en' => 'Welcome Discount',
                'discount_amount' => 10.000,
                'discount_type' => 'percentage',
                'usage_limit' => 100,
                'minimum_order_amount' => 100.000,
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(30),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'name_ar' => 'شحن مجاني',
                'name_en' => 'Free Shipping',
                'discount_amount' => 15.000,
                'discount_type' => 'fixed',
                'usage_limit' => 50,
                'minimum_order_amount' => 75.000,
                'start_date' => now(),
                'end_date' => now()->addDays(15),
                'is_active' => true,
            ]
        ];

        foreach ($coupons as $coupon) {
            $couponId = DB::table('coupons')->insertGetId([
                'code' => $coupon['code'],
                'discount_amount' => $coupon['discount_amount'],
                'discount_type' => $coupon['discount_type'],
                'usage_limit' => $coupon['usage_limit'],
                'usage_limit_per_user' => 3,
                'minimum_order_amount' => $coupon['minimum_order_amount'],
                'start_date' => $coupon['start_date'],
                'end_date' => $coupon['end_date'],
                'is_active' => $coupon['is_active'],
                'coupon_type' => 'regular',
                'object_type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('coupon_translations')->insert([
                [
                    'name' => $coupon['name_ar'],
                    'locale' => 'ar',
                    'coupon_id' => $couponId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => $coupon['name_en'],
                    'locale' => 'en',
                    'coupon_id' => $couponId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
