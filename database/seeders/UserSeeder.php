<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@maziwa.com'],
            [
                'name' => 'System Admin',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '+254700000001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'collector@maziwa.com'],
            [
                'name' => 'Milk Collector',
                'username' => 'collector',
                'password' => Hash::make('collector123'),
                'role' => 'collector',
                'phone' => '+254700000002',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
