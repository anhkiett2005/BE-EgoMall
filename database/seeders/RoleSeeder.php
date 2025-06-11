<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id'          => 1,
                'name'        => 'super-admin',
                'display_name'=> 'Super Administrator',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 2,
                'name'        => 'admin',
                'display_name'=> 'Quản trị viên',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 3,
                'name'        => 'staff',
                'display_name'=> 'Nhân viên',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 4,
                'name'        => 'customer',
                'display_name'=> 'Khách hàng',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}