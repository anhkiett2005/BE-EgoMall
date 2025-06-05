<?php

namespace Database\Factories;

use App\Models\VariantOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VariantValue>
 */
class VariantValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'option_id' => VariantOption::inRandomOrder()->first()->id ?? VariantOption::factory(),
            'value' => $this->faker->unique()->word(),
        ];
    }
}
