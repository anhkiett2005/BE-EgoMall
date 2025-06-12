<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Banner::insert([
            [
                'title' => 'Summer Sale',
                'image_url' => 'banners/summer.jpg',
                'link_url' => 'https://example.com/summer-sale',
                'position' => 'homepage',
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'New Arrivals',
                'image_url' => 'banners/new.jpg',
                'link_url' => 'https://example.com/new-arrivals',
                'position' => 'homepage',
                'start_date' => now(),
                'end_date' => now()->addDays(15),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
