<?php

namespace Modules\PaymentMethod\Database\Seeders;

use Illuminate\Database\Seeder;

class PaymentMethodDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Cash on Delivery',
                'code' => 'cash_on_delivery',
                'description' => 'Pay with cash when your order is delivered',
                'is_active' => true,
                'is_cod' => true,
                'sort_order' => 1,
            ],
        ];

        foreach ($paymentMethods as $method) {
            \Modules\PaymentMethod\Models\PaymentMethod::create($method);
        }
    }
}
