<?php

namespace Database\Seeders;

use App\Models\VariantOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VariantOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            'Màu sắc','Dung tích','Hương thơm','Chỉ số chống nắng','Dạng sản phẩm'
        ];

        foreach ($options as $option) {
            VariantOption::create(['name' => $option]);
        }
    }
}
