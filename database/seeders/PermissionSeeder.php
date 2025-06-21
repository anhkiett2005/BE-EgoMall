<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Danh sách các quyền đầy đủ
        $permissions = [
            ['name' => 'manage-products',           'display_name' => 'Quản lý sản phẩm'],
            ['name' => 'view-orders',               'display_name' => 'Xem đơn hàng'],
            ['name' => 'manage-users',              'display_name' => 'Quản lý người dùng'],
            ['name' => 'view-dashboard',            'display_name' => 'Xem dashboard'],
            ['name' => 'check-out',                 'display_name' => 'Thanh toán'],
            ['name' => 'manage-orders',             'display_name' => 'Quản lý đơn hàng'],
            ['name' => 'analyze-feedback',          'display_name' => 'Phân tích đánh giá'],
            ['name' => 'view-basic-stats',          'display_name' => 'Xem thống kê cơ bản'],
            ['name' => 'manage-categories',         'display_name' => 'Quản lý danh mục sản phẩm'],
            ['name' => 'manage-brands',             'display_name' => 'Quản lý thương hiệu'],
            ['name' => 'manage-promotions',         'display_name' => 'Quản lý khuyến mãi'],
            ['name' => 'manage-coupons',            'display_name' => 'Quản lý mã giảm giá'],
            ['name' => 'view-reviews',              'display_name' => 'Xem đánh giá sản phẩm'],
            ['name' => 'view-comments',             'display_name' => 'Xem bình luận'],
            ['name' => 'export-basic-report',       'display_name' => 'Xuất báo cáo cơ bản'],
            ['name' => 'export-advanced-report',    'display_name' => 'Xuất báo cáo nâng cao'],
            ['name' => 'manage-banners',            'display_name' => 'Quản lý banner'],
            ['name' => 'manage-sliders',            'display_name' => 'Quản lý slider'],
            ['name' => 'manage-posts',              'display_name' => 'Quản lý bài viết'],
            ['name' => 'manage-admin-accounts',     'display_name' => 'Quản lý tài khoản Admin'],
            ['name' => 'manage-admin-permissions',  'display_name' => 'Phân quyền chi tiết cho Admin'],
            ['name' => 'manage-system-config',      'display_name' => 'Cấu hình hệ thống'],
        ];

        // Tạo nếu chưa tồn tại
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Lấy các role
        $superAdmin = Role::where('name', 'super-admin')->first();
        $admin      = Role::where('name', 'admin')->first();
        $staff      = Role::where('name', 'staff')->first();
        $customer   = Role::where('name', 'customer')->first();

        // Helper lấy ID theo tên
        $getPermIds = fn(array $names): array =>
            Permission::whereIn('name', $names)->pluck('id')->toArray();

        // Gán permission cho staff
        if ($staff) {
            $staff->permissions()->sync($getPermIds([
                'manage-orders',
                'analyze-feedback',
                'view-basic-stats',
            ]));
        }

        // Gán permission cho admin (kế thừa staff + thêm)
        if ($admin) {
            $admin->permissions()->sync($getPermIds([
                // staff
                'manage-orders', 'analyze-feedback', 'view-basic-stats',
                // admin thêm
                'manage-users', 'manage-categories', 'manage-products',
                'manage-brands', 'manage-promotions', 'manage-coupons',
                'view-reviews', 'view-comments',
                'export-basic-report', 'export-advanced-report',
                'manage-banners', 'manage-sliders', 'manage-posts',
            ]));
        }

        // Gán permission cho customer
        if ($customer) {
            $customer->permissions()->sync($getPermIds([
                'check-out',
            ]));
        }

        // super-admin bỏ qua sync: toàn quyền, không cần gán cụ thể
    }
}