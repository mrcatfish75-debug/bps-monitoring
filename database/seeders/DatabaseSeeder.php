<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 🔥 ADMIN
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        // 🔥 KEPALA BPS
        User::create([
            'name' => 'Kepala BPS',
            'email' => 'kepala@gmail.com',
            'password' => Hash::make('123456'),
            'role' => 'kepala_bps',
        ]);

        // 🔥 KETUA TIM
        User::create([
            'name' => 'Ketua Tim',
            'email' => 'ketua@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'ketua_tim',
        ]);

        // 🔥 ANGGOTA
        User::create([
            'name' => 'Anggota 1',
            'email' => 'anggota@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'anggota',
        ]);
    }
}