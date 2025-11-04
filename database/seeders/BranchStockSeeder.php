<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\ProductAvailability;
use Modules\Store\Models\Store;
use Modules\Product\Models\Product;

class BranchStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all stores (main and branches)
        $stores = Store::all();

        // Get all products
        $products = Product::all();

        foreach ($stores as $store) {
            foreach ($products as $product) {
                // Check if availability already exists
                $existing = ProductAvailability::where('product_id', $product->id)
                    ->where('store_id', $store->id)
                    ->first();

                if (!$existing) {
                    // Create availability record for this product in this store
                    ProductAvailability::create([
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                        'stock_quantity' => rand(10, 100), // Random stock for demo
                        'is_in_stock' => true,
                        'available_start_date' => now(),
                        'available_end_date' => now()->addMonths(6),
                    ]);
                }
            }
        }

        $this->command->info('Branch stock data seeded successfully!');
    }
}
