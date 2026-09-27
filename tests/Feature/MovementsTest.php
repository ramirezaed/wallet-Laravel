<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_puede_transferir_dinero_entre_cuentas(): void
    {
        $usuarioOrigen = User::factory()->create();
        $usuarioDestino = User::factory()->create();

        $cuentaOrigen = $usuarioOrigen->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 500.00,
        ]);

        $cuentaDestino = $usuarioDestino->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 300.00,
        ]);

        $token = auth('api')->login($usuarioOrigen);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transfers', [
                'destination_cbu' => $cuentaDestino->cbu,
                'amount' => 150.00,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Transferencia realizada con éxito',
            ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaOrigen->id,
            'saldo' => '350.00',
        ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaDestino->id,
            'saldo' => '450.00',
        ]);

        $this->assertDatabaseHas('movimientos', [
            'cuenta_id' => $cuentaOrigen->id,
            'tipo' => 'transferencia_salida',
            'monto' => '150.00',
            'cbu_contraparte' => $cuentaDestino->cbu,
        ]);

        $this->assertDatabaseHas('movimientos', [
            'cuenta_id' => $cuentaDestino->id,
            'tipo' => 'transferencia_entrada',
            'monto' => '150.00',
            'cbu_contraparte' => $cuentaOrigen->cbu,
        ]);
    }

    public function test_usuario_no_puede_transferir_a_un_cbu_inexistente(): void
    {
        $usuarioOrigen = User::factory()->create();

        $cuentaOrigen = $usuarioOrigen->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 500.00,
        ]);

        $token = auth('api')->login($usuarioOrigen);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transfers', [
                'destination_cbu' => '9999999999999999999999',
                'amount' => 50.00,
            ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'la cuenta de origen o destino no existe',
            ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaOrigen->id,
            'saldo' => '500.00',
        ]);

        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_no_se_puede_transferir_un_monto_menor_o_igual_a_cero(): void
    {
        $usuarioOrigen = User::factory()->create();
        $usuarioDestino = User::factory()->create();

        $cuentaOrigen = $usuarioOrigen->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 500.00,
        ]);

        $cuentaDestino = $usuarioDestino->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 300.00,
        ]);

        $token = auth('api')->login($usuarioOrigen);

        foreach ([0, -10.50] as $monto) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/v1/transfers', [
                    'destination_cbu' => $cuentaDestino->cbu,
                    'amount' => $monto,
                ]);

            $response
                ->assertStatus(422)
                ->assertJson([
                    'message' => 'The amount field must be greater than 0.',
                ]);
        }

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaOrigen->id,
            'saldo' => '500.00',
        ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaDestino->id,
            'saldo' => '300.00',
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_hacer_una_transferencia(): void
    {
        $usuarioDestino = User::factory()->create();

        $cuentaDestino = $usuarioDestino->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 300.00,
        ]);

        $response = $this->postJson('/api/v1/transfers', [
            'destination_cbu' => $cuentaDestino->cbu,
            'amount' => 50.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_no_se_puede_transferir_al_mismo_cbu_de_la_cuenta_autenticada(): void
    {
        $usuarioOrigen = User::factory()->create();

        $cuentaOrigen = $usuarioOrigen->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 500.00,
        ]);

        $token = auth('api')->login($usuarioOrigen);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transfers', [
                'destination_cbu' => $cuentaOrigen->cbu,
                'amount' => 50.00,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'No se puede transferir a la misma cuenta',
            ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaOrigen->id,
            'saldo' => '500.00',
        ]);
    }

    public function test_no_se_puede_transferir_si_la_cuenta_origen_no_tiene_saldo_suficiente(): void
    {
        $usuarioOrigen = User::factory()->create();
        $usuarioDestino = User::factory()->create();

        $cuentaOrigen = $usuarioOrigen->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $cuentaDestino = $usuarioDestino->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 300.00,
        ]);

        $token = auth('api')->login($usuarioOrigen);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transfers', [
                'destination_cbu' => $cuentaDestino->cbu,
                'amount' => 150.00,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Saldo insuficiente para realizar la transferencia',
            ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaOrigen->id,
            'saldo' => '100.00',
        ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuentaDestino->id,
            'saldo' => '300.00',
        ]);

        $this->assertDatabaseCount('movimientos', 0);
    }
}
