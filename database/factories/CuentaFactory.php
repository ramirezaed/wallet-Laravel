<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuenta>
 */
class CuentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'cbu' => fake()->unique()->numerify('######################'),
            'saldo' => '0.00',
            'tipo' => 'ahorro',
            'moneda' => 'ARS',
        ];
    }
}
