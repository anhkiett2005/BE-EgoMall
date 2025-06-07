<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => Str::slug(fake()->words(3, true)),
            'category_id' => rand(1, 5),
            'sku' => strtoupper(Str::random(8)),
            'price' => fake()->numberBetween(100, 1000),
            'sale_price' => null,
            'quantity' => rand(10, 100),
            'stock_status' => 'in_stock',
            'is_variable' => false,
            'is_active' => true,
            'brand_id' => rand(1, 5),
            'type_skin' => fake()->randomElement(['da dầu','da khô','da mẫn cảm']),
            'description' => fake()->paragraph,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
