<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('coupons')->insert([
            [
                'code' => 'GIAM10',
                'description' => 'Giảm 10% cho đơn từ 100k',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_order_value' => 100000,
                'max_discount' => 50000,
                'usage_limit' => 100,
                'discount_limit' => 1,
                'start_date' => $now->toDateString(),
                'end_date' => $now->copy()->addDays(2)->toDateString(),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SALE50K',
                'description' => 'Giảm ngay 50k cho đơn từ 300k',
                'discount_type' => 'amount',
                'discount_value' => 50000,
                'min_order_value' => 300000,
                'max_discount' => null,
                'usage_limit' => 50,
                'discount_limit' => 2,
                'start_date' => $now->toDateString(),
                'end_date' => $now->copy()->addDays(3)->toDateString(),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'FREESHIP',
                'description' => 'Miễn phí vận chuyển đơn từ 150k',
                'discount_type' => 'amount',
                'discount_value' => 30000,
                'min_order_value' => 150000,
                'max_discount' => null,
                'usage_limit' => 100,
                'discount_limit' => 3,
                'start_date' => $now->toDateString(),
                'end_date' => $now->copy()->addDays(1)->toDateString(),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
