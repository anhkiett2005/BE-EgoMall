<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('id', '=', 5)->first();
        $variants = ProductVariant::inRandomOrder()->limit(5)->get();

        foreach(range(1,2) as $i) {
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => 0,
                'status' => 'ordered',
                'note' => "Order sample data $i",
                'shipping_address' => fake()->address(),
                'shipping_phone' => fake()->phoneNumber(),
                'created_at' => now(),
                'payment_method' => 'cod',
                'payment_status' => 'paid',
                'payment_date' => now(),
                'transaction_id' => fake()->uuid(),
            ]);

            $total = 0;

            foreach($variants->take(2) as $variant) {
                $qty = rand(1,3);
                $price = $variant->price;
                $salePrice = $variant->sale_price ?? null;
                $finalPrice = $salePrice > 0 ? $salePrice : $price;
                $total += $finalPrice * $qty;

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'is_gift' => false
                ]);
            }

            $order->update(['total_price' => $total]);


             // Review
            Review::create([
                'order_id' => $order->id,
                'rating' => rand(3, 5),
                'comment' => "This is a review for order #$i",
            ]);

            // Review image
            ReviewImage::create([
                'review_id' => $order->id, // vì order_id là khóa chính trong bảng reviews
                'image_url' => fake()->imageUrl(400, 400, 'review', true),
            ]);
        }
    }
}
