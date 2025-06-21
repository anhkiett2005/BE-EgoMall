<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy role_id từ bảng roles
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $adminRole      = Role::where('name', 'admin')->first();
        $staffRole      = Role::where('name', 'staff')->first();
        $customerRole   = Role::where('name', 'customer')->first();

        // Tạo user Super Admin
        User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name'      => 'Super Admin',
            'password'  => Hash::make('password'), // 🔐 Mật khẩu mặc định: password
            'role_id'   => $superAdminRole->id,
            'is_active' => true,
        ]);

        // Tạo user Admin
        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name'      => 'Admin User',
            'password'  => Hash::make('password'),
            'role_id'   => $adminRole->id,
            'is_active' => true,
        ]);

        // Tạo user Staff
        User::firstOrCreate([
            'email' => 'staff@example.com',
        ], [
            'name'      => 'Staff User',
            'password'  => Hash::make('password'),
            'role_id'   => $staffRole->id,
            'is_active' => true,
        ]);

        // Tạo user Customer
        User::firstOrCreate([
            'email' => 'customer@example.com',
        ], [
            'name'      => 'Customer User',
            'password'  => Hash::make('password'),
            'role_id'   => $customerRole->id,
            'is_active' => true,
        ]);
    }
}
