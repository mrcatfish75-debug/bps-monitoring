<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // VALIDASI WAJIB
        if (empty($row['name']) || empty($row['nip']) || empty($row['email'])) {
            return null;
        }

        // CEK DUPLIKAT (PENTING)
        if (
            User::where('email', $row['email'])->exists() ||
            User::where('nip', $row['nip'])->exists()
        ) {
            return null;
        }

        $password = Str::random(8);

        return new User([
            'name' => $row['name'],
            'nip' => $row['nip'],
            'email' => $row['email'], // 🔥 pakai dari Excel

            'role' => 'anggota',

            // login
            'password' => bcrypt($password),

            // admin lihat
            'plain_password' => $password,
            'is_default_password' => true,
        ]);
    }
}