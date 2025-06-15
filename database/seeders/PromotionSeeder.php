<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // === 1. Mua 3 tặng 1 ===
        $promotion1 = Promotion::create([
            'name' => 'Mua 3 tặng 1 sữa rửa mặt XYZ',
            'description' => 'Áp dụng cho các sản phẩm chăm sóc da. Mua 3 tặng 1 sản phẩm trị giá tương đương.',
            'promotion_type' => 'buy_get',
            'discount_type' => null,
            'discount_value' => null,
            'buy_quantity' => 3,
            'get_quantity' => 1,
            'gift_product_id' => null,
            'gift_product_variant_id' => 12,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(30),
            'status' => true,
        ]);

        // Gắn product_id: 1, 2, 3
        $productIds = ProductVariant::inRandomOrder()->take(10)->pluck('id')->toArray();
        foreach ($productIds as $productId) {
            PromotionProduct::create([
                'promotion_id' => $promotion1->id,
                'product_id' => null,
                'product_variant_id' => $productId
            ]);
        }

        // === 2. Giảm 20% ===
        $promotion2 = Promotion::create([
            'name' => 'Giảm 20% cho sản phẩm chăm sóc da',
            'description' => 'Giảm 20% áp dụng cho tất cả sản phẩm chăm sóc da.',
            'promotion_type' => 'percentage',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'buy_quantity' => null,
            'get_quantity' => null,
            'gift_product_id' => 11,
            'gift_product_variant_id' => null,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(15),
            'status' => true,
        ]);

        $productIdCommons = Product::inRandomOrder()->take(5)->pluck('id')->toArray();

        foreach ($productIdCommons as $productId) {
            PromotionProduct::create([
                'promotion_id' => $promotion2->id,
                'product_id' => $productId,
                'product_variant_id' => null,
            ]);
        }
    }
}
