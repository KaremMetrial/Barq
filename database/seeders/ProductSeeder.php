<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = DB::table('stores')->get();
        $categories = DB::table('categories')->get();
        $tags = ['وجبات سريعة', 'مشروبات', 'حلويات'];

        foreach ($tags as $tagName) {
            DB::table('tags')->insert([
                'name' => $tagName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = [
            [
                'name_ar' => 'بيج ماك',
                'name_en' => 'Big Mac',
                'description_ar' => 'برجر لذيذ مع صلصة خاصة',
                'description_en' => 'Delicious burger with special sauce',
                'price' => 65.000,
                'purchase_price' => 30.000,
                'barcode' => '1234567890123',
                'is_vegetarian' => false,
                'is_featured' => true,
                'max_cart_quantity' => 5,
            ],
            [
                'name_ar' => 'بيتزا بيبروني',
                'name_en' => 'Pepperoni Pizza',
                'description_ar' => 'بيتزا مع بيبروني وجبنة موتزاريلا',
                'description_en' => 'Pizza with pepperoni and mozzarella cheese',
                'price' => 85.000,
                'purchase_price' => 40.000,
                'barcode' => '1234567890124',
                'is_vegetarian' => false,
                'is_featured' => true,
                'max_cart_quantity' => 3,
            ],
            [
                'name_ar' => 'كولا',
                'name_en' => 'Cola',
                'description_ar' => 'مشروب غازي منعش',
                'description_en' => 'Refreshing carbonated drink',
                'price' => 15.000,
                'purchase_price' => 7.000,
                'barcode' => '1234567890125',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 10,
            ]
        ];

        foreach ($products as $index => $product) {
            $storeId = $stores[0]->id; // First store
            $categoryId = $index == 2 ? $categories[2]->id : $categories[$index]->id;

            $productId = DB::table('products')->insertGetId([
                'is_active' => true,
                'max_cart_quantity' => $product['max_cart_quantity'],
                'status' => 'active',
                'note' => 'منتج طازج وعالي الجودة',
                'is_reviewed' => true,
                'is_vegetarian' => $product['is_vegetarian'],
                'is_featured' => $product['is_featured'],
                'store_id' => $storeId,
                'category_id' => $categoryId,
                'barcode' => $product['barcode'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Product translations
            DB::table('product_translations')->insert([
                [
                    'name' => $product['name_ar'],
                    'description' => $product['description_ar'],
                    'locale' => 'ar',
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => $product['name_en'],
                    'description' => $product['description_en'],
                    'locale' => 'en',
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            // Product price
            DB::table('product_prices')->insert([
                'price' => $product['price'],
                'purchase_price' => $product['purchase_price'],
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Product availability
            DB::table('product_availabilities')->insert([
                'stock_quantity' => 100,
                'is_in_stock' => true,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Product images
            DB::table('product_images')->insert([
                [
                    'image_path' => 'uploads/products/product' . $productId . '_1.jpg',
                    'is_primary' => true,
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'image_path' => 'uploads/products/product' . $productId . '_2.jpg',
                    'is_primary' => false,
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            // Product tags
            $tagId = DB::table('tags')->where('name', 'وجبات سريعة')->first()->id;
            DB::table('product_tag')->insert([
                'product_id' => $productId,
                'tag_id' => $tagId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Product nutrition
            DB::table('product_nutrition')->insert([
                'calories' => rand(200, 800),
                'fat' => rand(5, 30),
                'protein' => rand(10, 40),
                'carbohydrates' => rand(20, 80),
                'sugar' => rand(5, 30),
                'fiber' => rand(1, 10),
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
