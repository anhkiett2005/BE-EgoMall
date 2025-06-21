<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super-admin', 'display_name' => 'Super Admin'],
            ['name' => 'admin',        'display_name' => 'Admin'],
            ['name' => 'staff',        'display_name' => 'Nhân viên'],
            ['name' => 'customer',     'display_name' => 'Khách hàng'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
