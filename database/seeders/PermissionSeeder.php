<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'manage-products', 'display_name' => 'Quản lý sản phẩm', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'view-orders', 'display_name' => 'Xem đơn hàng', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'manage-users', 'display_name' => 'Quản lý người dùng', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'view-reports', 'display_name' => 'Xem báo cáo', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'manage-categories', 'display_name' => 'Quản lý danh mục', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'manage-brands', 'display_name' => 'Quản lý thương hiệu', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
