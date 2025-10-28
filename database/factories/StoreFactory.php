<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Store\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Store\Models\Store::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => 'approved',
            'note' => fake()->sentence(),
            'message' => fake()->sentence(),
            'phone' => fake()->phoneNumber(),
            'is_featured' => fake()->boolean(),
            'is_active' => true,
            'is_closed' => false,
            'avg_rate' => fake()->randomFloat(1, 0, 5),
            'section_id' => null, // Set to null to avoid foreign key constraint
        ];
    }
}
