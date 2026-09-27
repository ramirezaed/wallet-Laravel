<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    // Agregamos test para verificar que un usuario autenticado puede consultar su cuenta
    public function test_usuario_autenticado_puede_consultar_su_cuenta(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 1500.50,
            'tipo' => 'ahorro',
            'moneda' => 'ARS',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJson([
                'cbu' => $account->cbu,
                'saldo' => '1500.50',
                'tipo' => $account->tipo,
                'moneda' => $account->moneda,
            ]);
    }

    // Agregamos test para verificar que un usuario sin token no puede consultar su cuenta
    public function test_usuario_sin_token_no_puede_consultar_su_cuenta(): void
    {
        $response = $this->getJson('/api/v1/account');

        $response->assertStatus(401);
    }

    // Agregamos test para verificar que un usuario con token inválido no puede consultar su cuenta
    public function test_usuario_solo_puede_ver_su_propia_cuenta(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = $user1->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $user2->cuenta()->create([
            'cbu' => '0000000000000000000002',
            'saldo' => 9999.99,
        ]);

        $token = auth('api')->login($user1);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJson([
                'cbu' => $account1->cbu,
                'saldo' => '100.00',
            ])
            ->assertJsonMissing([
                'cbu' => '0000000000000000000002',
            ]);
    }

    public function test_usuario_autenticado_puede_depositar_monto_en_su_cuenta(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $token = auth('api')->login($user);

        $depositAmount = 50.00;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/deposits', [
                'amount' => $depositAmount,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'cbu' => $account->cbu,
                'saldo' => number_format($account->saldo + $depositAmount, 2, '.', ''),
            ]);
    }

    public function test_usuario_autenticado_persiste_el_nuevo_saldo(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/deposits', [
                'amount' => 50.00,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('cuentas', [
            'id' => $account->id,
            'saldo' => '150.00',
        ]);
    }

    public function test_usuario_autenticado_registra_el_movimiento_del_deposito(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/deposits', [
                'amount' => 50.00,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('movimientos', [
            'cuenta_id' => $account->id,
            'tipo' => 'deposito',
            'monto' => '50.00',
        ]);
    }

    public function test_usuario_autenticado_no_deposita_negativo_o_cero_y_no_genera_movimiento(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $token = auth('api')->login($user);

        foreach ([-50.00, 0.00] as $depositAmount) {
            $response = $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/v1/deposits', [
                    'amount' => $depositAmount,
                ]);

            $response->assertStatus(422)->assertJson([
                'status' => 422,
            ])
                ->assertJsonStructure([
                    'message',
                    'status',
                    'error',
                ]);

        }

        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_usuario_autenticado_devuelve_nuevo_saldo(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 100.00,
        ]);

        $token = auth('api')->login($user);

        $depositAmount = 50.00;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/deposits', [
                'amount' => $depositAmount,
            ]);
        $response
            ->assertStatus(200)
            ->assertJson([
                'cbu' => $account->cbu,
                'saldo' => number_format($account->saldo + $depositAmount, 2, '.', ''),
            ]);
    }

    public function test_usuario_sin_token_no_puede_depositar(): void
    {
        $depositAmount = 50.00;

        $response = $this->postJson('/api/v1/deposits', [
            'amount' => $depositAmount,
        ]);

        $response->assertStatus(401);
    }

    public function test_cuenta_usuario_no_logueado_o_token_invalido(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer token-invalido')
            ->getJson('/api/v1/account');
        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'No autenticado',
                'status' => 401,
            ]);
    }

    public function test_cuenta_se_crea_con_tipo_y_moneda_por_defecto(): void
    {
        $user = User::factory()->create();

        $account = $user->cuenta()->create([
            'cbu' => '0000000000000000000001',
            'saldo' => 0,
        ]);

        $account->refresh();

        $this->assertEquals('ahorro', $account->tipo);
        $this->assertEquals('ARS', $account->moneda);

        $this->assertDatabaseHas('cuentas', [
            'id' => $account->id,
            'tipo' => 'ahorro',
            'moneda' => 'ARS',
        ]);
    }
}
