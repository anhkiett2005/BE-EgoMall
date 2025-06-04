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
    }
}
