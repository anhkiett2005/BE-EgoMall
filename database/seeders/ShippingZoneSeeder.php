<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        $shippingMethodId = 1; // Giao hàng tiêu chuẩn

        // Lấy danh sách tỉnh trừ TP.HCM (code = 79)
        $provinces = DB::table('provinces')
            ->where('code', '!=', '79')
            ->get(['code', 'name']);

        $data = [];

        foreach ($provinces as $province) {
            $fee = match (true) {
                in_array((int)$province->code, [31, 33, 34, 35, 36, 37, 38, 40]) => 30000, // Miền Trung
                in_array((int)$province->code, [1, 2, 4, 6, 8, 10, 11, 12]) => 40000, // Miền Bắc
                default => 25000, // Miền Nam còn lại
            };

            $data[] = [
                'shipping_method_id' => $shippingMethodId,
                'province_code' => $province->code,
                'fee' => $fee,
                'is_available' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        DB::table('shipping_zones')->insert($data);
    }
}