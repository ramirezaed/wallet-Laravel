<?php

namespace Database\Seeders;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        User::factory(20)->create([
            'password' => $password,
        ])->each(function (User $user): void {
            Cuenta::factory()->create([
                'usuario_id' => $user->id,
                'tipo' => 'ahorro',
                'moneda' => 'ARS',
            ]);
        });

        User::factory(2)->create([
            'password' => $password,
        ]);
    }
}
