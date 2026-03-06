<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::query()->updateOrCreate(
            ['email' => 'admin@xperts.my'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Xperts12345'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

                // Admin user
        User::query()->updateOrCreate(
            ['email' => 'evlease@xperts.my'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Xperts12345'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Driver user
        User::query()->updateOrCreate(
            ['email' => 'faiz@xperts.my'],
            [
                'name' => 'Driver User',
                'password' => Hash::make('Xperts12345'),
                'role' => UserRole::DRIVER,
                'is_active' => true,
                'email_verified_at' => now(),
                'driver_license' => 'DL123456', // Example driver license
                'phone' => '+60123456789',
                'date_of_birth' => '1990-01-01',
                'address' => '123 Jalan Example, Kuala Lumpur',
            ]
        );
    }
}