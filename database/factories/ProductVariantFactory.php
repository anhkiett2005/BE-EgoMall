<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => null, // set in seeder
            'sku' => strtoupper(Str::random(10)),
            'price' => fake()->numberBetween(100, 500),
            'sale_price' => null,
            'quantity' => rand(5, 50),
            'stock_status' => 'in_stock',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
