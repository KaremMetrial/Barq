<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\AddOn\Models\AddOn;
use Modules\Option\Models\Option;
use Modules\Product\Models\ProductOption;
use Modules\Product\Models\ProductValue;
use Modules\Product\Models\ProductOptionValue;

class ProductAddonOptionSeeder extends Seeder
{
    public function run(): void
    {
        // Get some existing products to attach add-ons and options
        $products = Product::take(5)->get(); // Take first 5 products

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please seed products first.');
            return;
        }

        // Seed Add-Ons
        $addOnData = [
            [
                'price' => 5.00,
                'is_active' => true,
                'applicable_to' => 'product',
                'name_ar' => 'إضافة جبن',
                'name_en' => 'Extra Cheese',
                'description_ar' => 'إضافة جبن إضافي',
                'description_en' => 'Add extra cheese',
            ],
            [
                'price' => 3.00,
                'is_active' => true,
                'applicable_to' => 'product',
                'name_ar' => 'إضافة صلصة',
                'name_en' => 'Extra Sauce',
                'description_ar' => 'إضافة صلصة إضافية',
                'description_en' => 'Add extra sauce',
            ],
            [
                'price' => 2.50,
                'is_active' => true,
                'applicable_to' => 'product',
                'name_ar' => 'إضافة خضار',
                'name_en' => 'Extra Vegetables',
                'description_ar' => 'إضافة خضار إضافية',
                'description_en' => 'Add extra vegetables',
            ],
        ];

        foreach ($addOnData as $data) {
            $addOnId = DB::table('add_ons')->insertGetId([
                'price' => $data['price'],
                'is_active' => $data['is_active'],
                'applicable_to' => $data['applicable_to'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('add_on_translations')->insert([
                [
                    'add_on_id' => $addOnId,
                    'locale' => 'ar',
                    'name' => $data['name_ar'],
                    'description' => $data['description_ar'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'add_on_id' => $addOnId,
                    'locale' => 'en',
                    'name' => $data['name_en'],
                    'description' => $data['description_en'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Attach add-on to products
            foreach ($products as $product) {
                DB::table('add_on_product')->insert([
                    'add_on_id' => $addOnId,
                    'product_id' => $product->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Seed Options
        $optionData = [
            [
                'name_ar' => 'الحجم',
                'name_en' => 'Size',
                'input_type' => 'single',
                'is_required' => true,
                'values' => [
                    ['name_ar' => 'صغير', 'name_en' => 'Small', 'price' => 0],
                    ['name_ar' => 'وسط', 'name_en' => 'Medium', 'price' => 5],
                    ['name_ar' => 'كبير', 'name_en' => 'Large', 'price' => 10],
                ],
            ],
            [
                'name_ar' => 'النكهة',
                'name_en' => 'Flavor',
                'input_type' => 'multiple',
                'is_required' => false,
                'values' => [
                    ['name_ar' => 'حار', 'name_en' => 'Spicy', 'price' => 2],
                    ['name_ar' => 'حلو', 'name_en' => 'Sweet', 'price' => 1],
                    ['name_ar' => 'عادي', 'name_en' => 'Regular', 'price' => 0],
                ],
            ],
        ];

        foreach ($optionData as $data) {
            $optionId = DB::table('options')->insertGetId([
                'input_type' => $data['input_type'],
                'is_food_option' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('option_translations')->insert([
                [
                    'option_id' => $optionId,
                    'locale' => 'ar',
                    'name' => $data['name_ar'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'option_id' => $optionId,
                    'locale' => 'en',
                    'name' => $data['name_en'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Create product options for each product
            foreach ($products as $product) {
                $productOptionId = DB::table('product_options')->insertGetId([
                    'product_id' => $product->id,
                    'option_id' => $optionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create product values
                foreach ($data['values'] as $valueData) {
                    $productValueId = DB::table('product_values')->insertGetId([
                        'option_id' => $productOptionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('product_value_translations')->insert([
                        [
                            'product_value_id' => $productValueId,
                            'locale' => 'ar',
                            'name' => $valueData['name_ar'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'product_value_id' => $productValueId,
                            'locale' => 'en',
                            'name' => $valueData['name_en'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);

                    // Create product option values
                    DB::table('product_option_values')->insert([
                        'product_value_id' => $productValueId,
                        'product_option_id' => $productOptionId,
                        'stock' => 100,
                        'price' => $valueData['price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Product Add-on and Option Seeder completed successfully.');
    }
}
