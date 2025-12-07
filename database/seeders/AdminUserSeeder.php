<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administradora',
            'email' => 'admin@laneria.com',
            'password' => Hash::make('admin123'),
            'rol' => 'administrador',
        ]);
    }
}