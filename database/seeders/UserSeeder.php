<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin',
    'email' => 'admin@gmail.com',
    'password' => Hash::make('12345678'),
    'role' => 'admin'
]);

User::create([
    'name' => 'Ketua',
    'email' => 'ketua@gmail.com',
    'password' => Hash::make('12345678'),
    'role' => 'ketua_tim'
]);
