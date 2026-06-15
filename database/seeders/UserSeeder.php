<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = 'Admin12345@';

        $users = [
            [
                'name' => 'Admin BPS',
                'email' => 'admin@bps.go.id',
                'role' => 'admin',
            ],
            [
                'name' => 'Kepala BPS',
                'email' => 'kepala@bps.go.id',
                'role' => 'kepala',
            ],
            [
                'name' => 'Ketua Tim BPS',
                'email' => 'ketua@bps.go.id',
                'role' => 'ketua',
            ],
            [
                'name' => 'Anggota BPS',
                'email' => 'anggota@bps.go.id',
                'role' => 'anggota',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make($defaultPassword),

                    /*
                    |--------------------------------------------------------------------------
                    | Default Password Flag
                    |--------------------------------------------------------------------------
                    | Jika project menggunakan fitur force change password,
                    | ubah is_default_password menjadi true.
                    |
                    | Untuk kebutuhan testing/demo agar langsung bisa login,
                    | dibuat false.
                    */
                    'is_default_password' => false,
                    'password_changed_at' => now(),
                    'password_reset_at' => null,
                    'password_reset_by' => null,
                ]
            );
        }
    }
}