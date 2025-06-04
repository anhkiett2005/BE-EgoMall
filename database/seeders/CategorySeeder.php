<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 5 danh mục gốc
        $rootCategories = Category::factory()->count(5)->create();

        // Tạo mỗi danh mục gốc 2–3 danh mục con
        foreach ($rootCategories as $parent) {
            Category::factory()
                ->count(rand(2, 3))
                ->create([
                    'parent_id' => $parent->id,
                ]);
        }

        $brandIds = \App\Models\Brand::pluck('id')->toArray();

        // Tạo danh mục theo brand
        Category::factory()
                ->count(5)
                ->create([
                    'brand_id' => fake()->randomElement($brandIds),
                    'parent_id' => null,
                ]);

        // Mỗi danh mục theo brand có thêm 1–2 danh mục con
        $brandCategories = Category::whereNotNull('brand_id')->get();
        foreach ($brandCategories as $parent) {
            Category::factory()
                ->count(rand(1, 2))
                ->create([
                    'parent_id' => $parent->id,
                    'brand_id' => $parent->brand_id, // giữ nguyên brand
                ]);
        }

    }
}
