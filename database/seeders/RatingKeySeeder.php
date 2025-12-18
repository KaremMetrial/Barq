<?php

namespace Database\Seeders;

use App\Models\RatingKey;
use Illuminate\Database\Seeder;

class RatingKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keys = [
            [
                'key' => 'food_quality',
                'en' => ['label' => 'Food Quality'],
                'ar' => ['label' => 'جودة الطعام'],
            ],
            [
                'key' => 'delivery_speed',
                'en' => ['label' => 'Delivery Speed'],
                'ar' => ['label' => 'سرعة التوصيل'],
            ],
            [
                'key' => 'order_execution_speed',
                'en' => ['label' => 'Order Execution Speed'],
                'ar' => ['label' => 'سرعة تنفيذ الطلب'],
            ],
            [
                'key' => 'product_quality',
                'en' => ['label' => 'Product Quality'],
                'ar' => ['label' => 'جودة المنتج'],
            ],
            [
                'key' => 'shopping_experience',
                'en' => ['label' => 'Shopping Experience'],
                'ar' => ['label' => 'تجربة التسوق'],
            ],
            [
                'key' => 'overall_experience',
                'en' => ['label' => 'Overall Experience'],
                'ar' => ['label' => 'التجربة العامة'],
            ],
            [
                'key' => 'delivery_driver',
                'en' => ['label' => 'Delivery Driver'],
                'ar' => ['label' => 'سائق التوصيل'],
            ],
            [
                'key' => 'delivery_condition',
                'en' => ['label' => 'Delivery Condition'],
                'ar' => ['label' => 'حالة التوصيل'],
            ],
            [
                'key' => 'match_price',
                'en' => ['label' => 'Price Match'],
                'ar' => ['label' => 'مطابقة السعر'],
            ],
        ];

        foreach ($keys as $keyData) {
            RatingKey::updateOrCreate(
                ['key' => $keyData['key']],
                $keyData
            );
        }
    }
}
