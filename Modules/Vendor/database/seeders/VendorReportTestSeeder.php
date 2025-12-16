<?php

namespace Modules\Vendor\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Vendor\Models\Vendor;
use Modules\Store\Models\Store;
use Modules\Order\Models\Order;
use Modules\Balance\Models\Balance;
use App\Models\Transaction;
use Modules\Setting\Models\Setting;
use Illuminate\Support\Facades\DB;

class VendorReportTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing test data
        DB::table('settings')->where('key', 'like', 'store_currency_%')->delete();
        DB::table('settings')->where('key', 'currency')->delete();

        // Create stores with different currencies
        $stores = [
            [
                'id' => 1001,
                'name' => ['en' => 'USD Store', 'ar' => 'متجر دولار'],
                'currency' => 'USD',
                'vendor_data' => [
                    'email' => 'usd_vendor@example.com',
                    'phone' => '1234567891'
                ]
            ],
            [
                'id' => 1002,
                'name' => ['en' => 'EUR Store', 'ar' => 'متجر يورو'],
                'currency' => 'EUR',
                'vendor_data' => [
                    'email' => 'eur_vendor@example.com',
                    'phone' => '1234567892'
                ]
            ],
            [
                'id' => 1003,
                'name' => ['en' => 'KWD Store (Default)', 'ar' => 'متجر دينار (افتراضي)'],
                'currency' => null, // Will use default KWD
                'vendor_data' => [
                    'email' => 'kwd_vendor@example.com',
                    'phone' => '1234567893'
                ]
            ]
        ];

        foreach ($stores as $storeData) {
            $this->createStoreWithVendorAndData($storeData);
        }

        // Create global currency setting for store 1002 to test fallback
        Setting::create([
            'key' => 'currency',
            'value' => 'EUR',
            'type' => 'string'
        ]);

        echo "✅ Vendor Report Test Seeder Completed!\n";
        echo "📊 Created 3 test stores with different currency configurations:\n";
        echo "- Store 1001: USD (store-specific setting)\n";
        echo "- Store 1002: EUR (global setting fallback)\n";
        echo "- Store 1003: KWD (default, no settings)\n";
        echo "\n🚀 Ready to test the vendor report endpoint!\n";
    }

    protected function createStoreWithVendorAndData(array $storeData): void
    {
        // Create store
        $store = Store::create([
            'id' => $storeData['id'],
            'status' => 'approved',
            'is_active' => true,
            'section_id' => 1,
            'commission_type' => 'percentage',
            'commission_amount' => 10.0
        ]);

        // Add store translations
        DB::table('store_translations')->insert([
            [
                'store_id' => $store->id,
                'locale' => 'en',
                'name' => $storeData['name']['en']
            ],
            [
                'store_id' => $store->id,
                'locale' => 'ar',
                'name' => $storeData['name']['ar']
            ]
        ]);

        // Create store-specific currency setting if currency is specified
        if ($storeData['currency']) {
            Setting::create([
                'key' => 'store_currency_' . $store->id,
                'value' => $storeData['currency'],
                'type' => 'string'
            ]);
        }

        // Create vendor
        $vendor = Vendor::create([
            'first_name' => 'Test',
            'last_name' => 'Vendor',
            'email' => $storeData['vendor_data']['email'],
            'phone' => $storeData['vendor_data']['phone'],
            'password' => bcrypt('password'),
            'is_active' => true,
            'store_id' => $store->id,
            'is_owner' => true
        ]);

        // Create balance for vendor
        Balance::create([
            'balanceable_id' => $vendor->id,
            'balanceable_type' => Vendor::class,
            'total_balance' => 25000.000,
            'available_balance' => 16000.000,
            'pending_balance' => 9000.000
        ]);

        // Create test orders for the store
        $this->createTestOrdersForStore($store->id, $storeData['currency'] ?? 'KWD');

        // Create test transactions for the vendor
        $this->createTestTransactionsForVendor($vendor->id, $storeData['currency'] ?? 'KWD');
    }

    protected function createTestOrdersForStore(int $storeId, string $currency): void
    {
        $orders = [
            [
                'order_number' => 'ORD-' . $storeId . '-001',
                'type' => 'deliver',
                'status' => 'delivered',
                'total_amount' => 2500.500,
                'created_at' => now()
            ],
            [
                'order_number' => 'ORD-' . $storeId . '-002',
                'type' => 'deliver',
                'status' => 'delivered',
                'total_amount' => 1500.750,
                'created_at' => now()->subHours(2)
            ],
            [
                'order_number' => 'ORD-' . $storeId . '-003',
                'type' => 'pickup',
                'status' => 'delivered',
                'total_amount' => 3000.000,
                'created_at' => now()->subHours(5)
            ]
        ];

        foreach ($orders as $orderData) {
            Order::create([
                'order_number' => $orderData['order_number'],
                'type' => $orderData['type'],
                'status' => $orderData['status'],
                'total_amount' => $orderData['total_amount'],
                'store_id' => $storeId,
                'user_id' => 1,
                'created_at' => $orderData['created_at']
            ]);
        }
    }

    protected function createTestTransactionsForVendor(int $vendorId, string $currency): void
    {
        $transactions = [
            [
                'type' => 'withdrawal',
                'amount' => 20000.000,
                'description' => 'Withdrawal from wallet',
                'created_at' => now()->subDays(1)
            ],
            [
                'type' => 'commission',
                'amount' => 5000.000,
                'description' => 'Commission payment',
                'created_at' => now()->subDays(2)
            ],
            [
                'type' => 'deposit',
                'amount' => 10000.000,
                'description' => 'Deposit to wallet',
                'created_at' => now()->subDays(3)
            ]
        ];

        foreach ($transactions as $transactionData) {
            Transaction::create([
                'user_id' => $vendorId,
                'type' => $transactionData['type'],
                'amount' => $transactionData['amount'],
                'currency' => $currency,
                'description' => $transactionData['description'],
                'created_at' => $transactionData['created_at']
            ]);
        }
    }
}
