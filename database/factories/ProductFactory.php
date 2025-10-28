<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Product\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Product\Models\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
            'max_cart_quantity' => fake()->numberBetween(1, 100),
            'status' => 'active',
            'note' => fake()->sentence(),
            'is_reviewed' => fake()->boolean(),
            'is_vegetarian' => fake()->boolean(),
            'is_featured' => fake()->boolean(),
            'store_id' => 1, // Assuming store exists
            'category_id' => 1, // Assuming category exists
            'barcode' => fake()->ean13(),
        ];
    }
}
