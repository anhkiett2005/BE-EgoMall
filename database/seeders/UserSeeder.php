<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@egomall.local',
            'email_verified_at' => now(),
            'password'          => Hash::make('Password123!'),
            'phone'             => '0123456789',
            'address'           => 'Hanoi, Vietnam',
            'role_id'           => 1,  // **Super Admin**
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('users')->insert([
            'name'              => 'Tran Thi B',
            'email'             => 'admin@egomall.local',
            'email_verified_at' => now(),
            'password'          => Hash::make('Admin123!'),
            'phone'             => '0912345678',
            'address'           => 'Da Nang, Vietnam',
            'role_id'           => 2,  // **Admin**
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('users')->insert([
            'name'              => 'Le Van C',
            'email'             => 'staff@egomall.local',
            'email_verified_at' => now(),
            'password'          => Hash::make('Staff123!'),
            'phone'             => '0909876543',
            'address'           => 'Hue, Vietnam',
            'role_id'           => 3,  // **Staff (Nhân viên)**
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('users')->insert([
            'name'              => 'Nguyen Van A',
            'email'             => 'nguyenvana@egomall.local',
            'email_verified_at' => now(),
            'password'          => Hash::make('User12345!'),
            'phone'             => '0987654321',
            'address'           => 'HCM City, Vietnam',
            'role_id'           => 4,
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}