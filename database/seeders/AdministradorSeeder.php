<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@wallet.local')],
            [
                'nombre' => env('ADMIN_NOMBRE', 'Administrador'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'edad' => 30,
                'rol' => 'administrador',
            ],
        );
    }
}
