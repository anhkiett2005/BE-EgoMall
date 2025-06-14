<?php

namespace Database\Seeders;

use App\Models\CategoryOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = DB::table('categories')->take(5)->pluck('id')->toArray();
        $optionIds = DB::table('variant_options')->take(5)->pluck('id')->toArray();

        foreach ($categoryIds as $categoryId) {
            // Mỗi category sẽ gán ngẫu nhiên 2-3 option
            $assignedOptions = collect($optionIds)->shuffle()->take(rand(2, 3));

            foreach ($assignedOptions as $optionId) {
                CategoryOption::create([
                    'category_id' => $categoryId,
                    'variant_option_id' => $optionId
                ]);
            }
        }
    }
}
