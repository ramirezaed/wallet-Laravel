<?php

namespace Tests\Feature;

use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMovementTest extends TestCase
{
    use RefreshDatabase;

    private function adminConCuenta(float $saldo = 1000): array
    {
        $admin = User::factory()->create([
            'rol' => 'administrador',
        ]);

        $cuenta = $admin->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => $saldo,
            'tipo' => 'ahorro',
            'moneda' => 'ARS',
        ]);

        $token = auth('api')->login($admin);

        return [$admin, $cuenta, $token];
    }

    public function test_admin_puede_listar_movimientos(): void
    {
        [$admin, $cuenta, $token] = $this->adminConCuenta();

        Movimiento::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'monto' => 100,
            'cbu_contraparte' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/movements');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_usuario_comun_no_puede_acceder_a_movimientos_admin(): void
    {
        $user = User::factory()->create([
            'rol' => 'usuario',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/movements');

        $response
            ->assertStatus(403)
            ->assertJson([
                'status' => 403,
            ]);
    }

    public function test_usuario_sin_token_no_puede_acceder_a_movimientos_admin(): void
    {
        $response = $this->getJson('/api/v1/admin/movements');

        $response->assertStatus(401);
    }

    public function test_admin_puede_crear_movimiento(): void
    {
        [$admin, $cuenta, $token] = $this->adminConCuenta();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/admin/movements', [
                'cuenta_id' => $cuenta->id,
                'tipo' => 'deposito',
                'monto' => 250,
                'cbu_contraparte' => null,
            ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'tipo' => 'deposito',
                'monto' => '250.00',
            ]);

        $this->assertDatabaseHas('movimientos', [
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'monto' => '250.00',
        ]);
    }

    public function test_admin_puede_consultar_un_movimiento(): void
    {
        [$admin, $cuenta, $token] = $this->adminConCuenta();

        $movimiento = Movimiento::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'monto' => 100,
            'cbu_contraparte' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/admin/movements/{$movimiento->id}");

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $movimiento->id,
                'tipo' => 'deposito',
                'monto' => '100.00',
            ]);
    }

    public function test_admin_puede_actualizar_movimiento_sin_modificar_saldo(): void
    {
        [$admin, $cuenta, $token] = $this->adminConCuenta(1000);

        $movimiento = Movimiento::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'monto' => 100,
            'cbu_contraparte' => null,
        ]);

        $saldoOriginal = $cuenta->saldo;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/admin/movements/{$movimiento->id}", [
                'monto' => 500,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'monto' => '500.00',
            ]);

        $this->assertDatabaseHas('movimientos', [
            'id' => $movimiento->id,
            'monto' => '500.00',
        ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuenta->id,
            'saldo' => $saldoOriginal,
        ]);
    }

    public function test_admin_puede_eliminar_movimiento_sin_modificar_saldo(): void
    {
        [$admin, $cuenta, $token] = $this->adminConCuenta(1000);

        $movimiento = Movimiento::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => 'deposito',
            'monto' => 100,
            'cbu_contraparte' => null,
        ]);

        $saldoOriginal = $cuenta->saldo;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/admin/movements/{$movimiento->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('movimientos', [
            'id' => $movimiento->id,
        ]);

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuenta->id,
            'saldo' => $saldoOriginal,
        ]);
    }

    public function test_listado_admin_puede_filtrar_por_cuenta(): void
    {
        [$admin, $cuenta1, $token] = $this->adminConCuenta();

        $otroUsuario = User::factory()->create();

        $cuenta2 = $otroUsuario->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 500,
            'tipo' => 'ahorro',
            'moneda' => 'ARS',
        ]);

        Movimiento::create([
            'cuenta_id' => $cuenta1->id,
            'tipo' => 'deposito',
            'monto' => 100,
        ]);

        Movimiento::create([
            'cuenta_id' => $cuenta2->id,
            'tipo' => 'deposito',
            'monto' => 200,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/admin/movements?cuenta_id={$cuenta1->id}");

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cuenta.id', $cuenta1->id);
    }

    public function test_per_page_mayor_a_100_devuelve_422(): void
    {
        [$admin, $cuenta, $token] = $this->adminConCuenta();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/movements?per_page=101');

        $response->assertStatus(422);
    }
}
