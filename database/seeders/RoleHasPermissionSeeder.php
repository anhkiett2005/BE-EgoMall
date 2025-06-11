<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleHasPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Ví dụ: Gán permission "manage-products" (id=1) và "manage-orders"(id=3) cho role admin (id=3)
        DB::table('role_has_permissions')->insert([
            ['role_id' => 3, 'permission_id' => 1], // Admin quản lý sản phẩm
            ['role_id' => 3, 'permission_id' => 3], // Admin quản lý đơn hàng

            // Quản lý đơn hàng (view-orders, manage-orders) cho staff (id=4)
            ['role_id' => 4, 'permission_id' => 2], // staff xem đơn hàng
            ['role_id' => 4, 'permission_id' => 3], // staff quản lý đơn hàng
        ]);
    }
}
