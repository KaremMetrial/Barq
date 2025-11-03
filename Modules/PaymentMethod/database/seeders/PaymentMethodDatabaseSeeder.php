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
            [
                'name' => 'Credit Card',
                'code' => 'credit_card',
                'description' => 'Pay with credit card online',
                'is_active' => true,
                'is_cod' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Debit Card',
                'code' => 'debit_card',
                'description' => 'Pay with debit card online',
                'is_active' => true,
                'is_cod' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'description' => 'Pay via bank transfer',
                'is_active' => true,
                'is_cod' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Digital Wallet',
                'code' => 'digital_wallet',
                'description' => 'Pay using digital wallet services',
                'is_active' => true,
                'is_cod' => false,
                'sort_order' => 5,
            ],
        ];

        foreach ($paymentMethods as $method) {
            \Modules\PaymentMethod\Models\PaymentMethod::create($method);
        }
    }
}
