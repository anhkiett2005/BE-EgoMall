<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ShippingZoneExpressSeeder extends Seeder
{
    public function run(): void
    {
        $shippingMethodId = 2;

        // Danh sách tỉnh áp dụng: TP.HCM + tỉnh lân cận
        $provinceCodes = ['79', '75', '80', '72'];
        // 79: TP.HCM
        // 75: Đồng Nai
        // 80: Long An
        // 72: Bình Dương


        $data = [];

        foreach ($provinceCodes as $code) {
            $data[] = [
                'shipping_method_id' => $shippingMethodId,
                'province_code' => $code,
                'fee' => 45000,
                'is_available' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        DB::table('shipping_zones')->insert($data);
    }
}