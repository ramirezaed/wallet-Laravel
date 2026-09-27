<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movimiento>
 */
class MovimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cuenta_id'       => Cuenta::factory(),
            'tipo'            => $this->faker->randomElement(['deposito', 'transferencia', 'retiro']),
            'monto'           => $this->faker->randomFloat(2, 10, 5000),
            'cbu_contraparte' => $this->faker->numerify('######################'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }
    public function sinContraparte(): static
    {
        return $this->state(fn(array $attributes) => [
            'cbu_contraparte' => null,
        ]);
    }
}
