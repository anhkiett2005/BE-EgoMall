<?php

namespace Database\Seeders;

use App\Models\Sliders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         Sliders::insert([
            [
                'name' => 'Home Page Slider',
                'description' => 'Main slider for homepage',
                'position' => 'homepage',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Promotions Slider',
                'description' => 'Slider for promotions',
                'position' => 'promotions',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
