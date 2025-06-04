<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'parent_id' => null, // mặc định là danh mục gốc
            'description' => $this->faker->sentence(),
            'thumbnail' => $this->faker->imageUrl(),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20),
        ];
    }
}
