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

        // Create tags if they don't exist
        $tags = [
            ['name' => 'وجبات سريعة', 'name_en' => 'Fast Food'],
            ['name' => 'مشروبات', 'name_en' => 'Beverages'],
            ['name' => 'حلويات', 'name_en' => 'Desserts'],
            ['name' => 'أدوية', 'name_en' => 'Medicines'],
            ['name' => 'مستلزمات طبية', 'name_en' => 'Medical Supplies'],
            ['name' => 'عناية شخصية', 'name_en' => 'Personal Care'],
        ];

        foreach ($tags as $tag) {
            $existingTag = DB::table('tags')->where('name', $tag['name'])->first();
            if (!$existingTag) {
                DB::table('tags')->insert([
                    'name' => $tag['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Restaurant products
        $restaurantProducts = [
            // McDonald's products
            [
                'store_index' => 0, // McDonald's
                'category_index' => 0, // Burgers
                'name_ar' => 'بيج ماك',
                'name_en' => 'Big Mac',
                'description_ar' => 'برجر لذيذ مع صلصة خاصة ولحمتين كبيرتين',
                'description_en' => 'Delicious burger with special sauce and two large patties',
                'price' => 65.000,
                'purchase_price' => 30.000,
                'barcode' => '100000000101',
                'is_vegetarian' => false,
                'is_featured' => true,
                'max_cart_quantity' => 5,
                'tags' => ['وجبات سريعة'],
            ],
            [
                'store_index' => 0,
                'category_index' => 0,
                'name_ar' => 'تشيز برجر',
                'name_en' => 'Cheese Burger',
                'description_ar' => 'برجر مع جبنة تشيدر ولحمة طرية',
                'description_en' => 'Burger with cheddar cheese and tender patty',
                'price' => 45.000,
                'purchase_price' => 20.000,
                'barcode' => '100000000102',
                'is_vegetarian' => false,
                'is_featured' => false,
                'max_cart_quantity' => 5,
                'tags' => ['وجبات سريعة'],
            ],
            [
                'store_index' => 0,
                'category_index' => 1, // Pizza
                'name_ar' => 'ماك تشيكن',
                'name_en' => 'McChicken',
                'description_ar' => 'دجاج مقرمش مع صلصة مايونيز',
                'description_en' => 'Crispy chicken with mayonnaise sauce',
                'price' => 55.000,
                'purchase_price' => 25.000,
                'barcode' => '100000000103',
                'is_vegetarian' => false,
                'is_featured' => false,
                'max_cart_quantity' => 5,
                'tags' => ['وجبات سريعة'],
            ],
            [
                'store_index' => 0,
                'category_index' => 1,
                'name_ar' => 'كولا كبيرة',
                'name_en' => 'Large Cola',
                'description_ar' => 'مشروب كولا منعش بحجم كبير',
                'description_en' => 'Refreshing cola drink large size',
                'price' => 15.000,
                'purchase_price' => 7.000,
                'barcode' => '100000000104',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 10,
                'tags' => ['مشروبات'],
            ],
            [
                'store_index' => 0,
                'category_index' => 2, // Asian Food
                'name_ar' => 'فرايز كبيرة',
                'name_en' => 'Large Fries',
                'description_ar' => 'بطاطس مقلية ذهبية بحجم كبير',
                'description_en' => 'Golden fried potatoes large size',
                'price' => 25.000,
                'purchase_price' => 12.000,
                'barcode' => '100000000105',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 5,
                'tags' => ['وجبات سريعة'],
            ],

            // Pizza Hut products
            [
                'store_index' => 1, // Pizza Hut
                'category_index' => 1,
                'name_ar' => 'بيتزا بيبروني كبيرة',
                'name_en' => 'Large Pepperoni Pizza',
                'description_ar' => 'بيتزا كبيرة مع بيبروني وجبنة موتزاريلا',
                'description_en' => 'Large pizza with pepperoni and mozzarella cheese',
                'price' => 120.000,
                'purchase_price' => 60.000,
                'barcode' => '100000000106',
                'is_vegetarian' => false,
                'is_featured' => true,
                'max_cart_quantity' => 3,
                'tags' => ['وجبات سريعة'],
            ],
            [
                'store_index' => 1,
                'category_index' => 1,
                'name_ar' => 'بيتزا مارغريتا',
                'name_en' => 'Margherita Pizza',
                'description_ar' => 'بيتزا مع صلصة طماطم وجبنة موتزاريلا وبازيلاء',
                'description_en' => 'Pizza with tomato sauce, mozzarella cheese and basil',
                'price' => 95.000,
                'purchase_price' => 45.000,
                'barcode' => '100000000107',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 3,
                'tags' => ['وجبات سريعة'],
            ],
            [
                'store_index' => 1,
                'category_index' => 1,
                'name_ar' => 'بيتزا تشيكن باربيكيو',
                'name_en' => 'Chicken BBQ Pizza',
                'description_ar' => 'بيتزا مع دجاج مشوي وصلصة باربيكيو',
                'description_en' => 'Pizza with grilled chicken and BBQ sauce',
                'price' => 135.000,
                'purchase_price' => 65.000,
                'barcode' => '100000000108',
                'is_vegetarian' => false,
                'is_featured' => false,
                'max_cart_quantity' => 3,
                'tags' => ['وجبات سريعة'],
            ],

            // KFC products
            [
                'store_index' => 2, // KFC
                'category_index' => 0,
                'name_ar' => 'دجاج مقرمش',
                'name_en' => 'Crispy Chicken',
                'description_ar' => 'دجاج مقرمش مع توابل سرية',
                'description_en' => 'Crispy chicken with secret spices',
                'price' => 75.000,
                'purchase_price' => 35.000,
                'barcode' => '100000000109',
                'is_vegetarian' => false,
                'is_featured' => true,
                'max_cart_quantity' => 5,
                'tags' => ['وجبات سريعة'],
            ],
            [
                'store_index' => 2,
                'category_index' => 0,
                'name_ar' => 'وجبة عائلية',
                'name_en' => 'Family Meal',
                'description_ar' => 'وجبة عائلية تحتوي على دجاج وفرايز ومشروبات',
                'description_en' => 'Family meal with chicken, fries and drinks',
                'price' => 180.000,
                'purchase_price' => 85.000,
                'barcode' => '100000000110',
                'is_vegetarian' => false,
                'is_featured' => false,
                'max_cart_quantity' => 2,
                'tags' => ['وجبات سريعة'],
            ],

            // Starbucks products
            [
                'store_index' => 3, // Starbucks
                'category_index' => 1,
                'name_ar' => 'لاتيه',
                'name_en' => 'Latte',
                'description_ar' => 'قهوة لاتيه مع حليب طازج',
                'description_en' => 'Latte coffee with fresh milk',
                'price' => 35.000,
                'purchase_price' => 15.000,
                'barcode' => '100000000111',
                'is_vegetarian' => true,
                'is_featured' => true,
                'max_cart_quantity' => 10,
                'tags' => ['مشروبات'],
            ],
            [
                'store_index' => 3,
                'category_index' => 1,
                'name_ar' => 'كابتشينو',
                'name_en' => 'Cappuccino',
                'description_ar' => 'كابتشينو إيطالي أصيل',
                'description_en' => 'Authentic Italian cappuccino',
                'price' => 40.000,
                'purchase_price' => 18.000,
                'barcode' => '100000000112',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 10,
                'tags' => ['مشروبات'],
            ],
            [
                'store_index' => 3,
                'category_index' => 2,
                'name_ar' => 'كرواسون بالشوكولاتة',
                'name_en' => 'Chocolate Croissant',
                'description_ar' => 'كرواسون طازج محشو بالشوكولاتة',
                'description_en' => 'Fresh croissant filled with chocolate',
                'price' => 25.000,
                'purchase_price' => 12.000,
                'barcode' => '100000000113',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 5,
                'tags' => ['حلويات'],
            ],
        ];

        // Pharmacy products
        $pharmacyProducts = [
            // Al Nahda Pharmacy products
            [
                'store_index' => 8, // First pharmacy (index 8 in combined stores array)
                'category_index' => 0,
                'name_ar' => 'بانادول',
                'name_en' => 'Panadol',
                'description_ar' => 'مسكن للألم والحمى',
                'description_en' => 'Pain reliever and fever reducer',
                'price' => 25.000,
                'purchase_price' => 15.000,
                'barcode' => '200000000101',
                'is_vegetarian' => true,
                'is_featured' => true,
                'max_cart_quantity' => 5,
                'tags' => ['أدوية'],
            ],
            [
                'store_index' => 8,
                'category_index' => 0,
                'name_ar' => 'فيتامين C',
                'name_en' => 'Vitamin C',
                'description_ar' => 'فيتامين C لتعزيز المناعة',
                'description_en' => 'Vitamin C for immunity boost',
                'price' => 45.000,
                'purchase_price' => 25.000,
                'barcode' => '200000000102',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 10,
                'tags' => ['مستلزمات طبية'],
            ],
            [
                'store_index' => 8,
                'category_index' => 1,
                'name_ar' => 'كمامات طبية',
                'name_en' => 'Medical Masks',
                'description_ar' => 'كمامات طبية واقية',
                'description_en' => 'Protective medical masks',
                'price' => 15.000,
                'purchase_price' => 8.000,
                'barcode' => '200000000103',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 20,
                'tags' => ['مستلزمات طبية'],
            ],
            [
                'store_index' => 8,
                'category_index' => 2,
                'name_ar' => 'معقم يدين',
                'name_en' => 'Hand Sanitizer',
                'description_ar' => 'معقم يدين فعال بنسبة 70%',
                'description_en' => '70% effective hand sanitizer',
                'price' => 20.000,
                'purchase_price' => 10.000,
                'barcode' => '200000000104',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 10,
                'tags' => ['عناية شخصية'],
            ],

            // Al Riyadh Pharmacy products
            [
                'store_index' => 9, // Second pharmacy
                'category_index' => 0,
                'name_ar' => 'أموكسيسيلين',
                'name_en' => 'Amoxicillin',
                'description_ar' => 'مضاد حيوي واسع الطيف',
                'description_en' => 'Broad spectrum antibiotic',
                'price' => 35.000,
                'purchase_price' => 20.000,
                'barcode' => '200000000105',
                'is_vegetarian' => true,
                'is_featured' => true,
                'max_cart_quantity' => 3,
                'tags' => ['أدوية'],
            ],
            [
                'store_index' => 9,
                'category_index' => 1,
                'name_ar' => 'ضمادات طبية',
                'name_en' => 'Medical Bandages',
                'description_ar' => 'ضمادات طبية معقمة',
                'description_en' => 'Sterilized medical bandages',
                'price' => 12.000,
                'purchase_price' => 6.000,
                'barcode' => '200000000106',
                'is_vegetarian' => true,
                'is_featured' => false,
                'max_cart_quantity' => 15,
                'tags' => ['مستلزمات طبية'],
            ],
        ];

        $allProducts = array_merge($restaurantProducts, $pharmacyProducts);

        foreach ($allProducts as $index => $product) {
            $storeId = $stores[$product['store_index']]->id ?? $stores[0]->id;
            $categoryId = $categories[$product['category_index']]->id ?? $categories[0]->id;

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
                'stock_quantity' => rand(50, 200),
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
            foreach ($product['tags'] as $tagName) {
                $tag = DB::table('tags')->where('name', $tagName)->first();
                if ($tag) {
                    DB::table('product_tag')->insert([
                        'product_id' => $productId,
                        'tag_id' => $tag->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Product nutrition (only for food products)
            if (in_array($product['store_index'], [0, 1, 2, 3, 4, 5, 6, 7])) { // Restaurant stores
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
}
