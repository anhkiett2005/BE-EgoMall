<?php

namespace Database\Seeders;

use App\Models\Slider_images;
use App\Models\Sliders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SliderImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slider = Sliders::first(); // lấy slider đầu tiên

        if ($slider) {
            Slider_images::insert([
                [
                    'slider_id' => $slider->id,
                    'image_url' => 'sliders/slide1.jpg',
                    'link_url' => 'https://example.com/promo1',
                    'start_date' => now(),
                    'end_date' => now()->addDays(10),
                    'status' => 1,
                    'display_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slider_id' => $slider->id,
                    'image_url' => 'sliders/slide2.jpg',
                    'link_url' => 'https://example.com/promo2',
                    'start_date' => now(),
                    'end_date' => now()->addDays(15),
                    'status' => 1,
                    'display_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
