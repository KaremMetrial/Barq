<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Product\Models\ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Product\Models\ProductOptionValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_value_id' => 1, // Assuming product value exists
            'product_option_id' => 1, // Assuming product option exists
            'stock' => fake()->numberBetween(0, 100),
            'is_default' => fake()->boolean(),
            'price' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
