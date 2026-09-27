<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrador_puede_listar_cuentas_ordenadas_por_nombre_de_usuario(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $primero = User::factory()->create(['nombre' => 'Ana Cuenta']);
        $segundo = User::factory()->create(['nombre' => 'Zoe Cuenta']);

        $cuentaPrimero = Cuenta::factory()->create([
            'usuario_id' => $primero->id,
            'saldo' => 1250.50,
        ]);
        $cuentaSegundo = Cuenta::factory()->create([
            'usuario_id' => $segundo->id,
            'saldo' => 850.25,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->getJson('/api/v1/admin/accounts?orden=asc&per_page=2');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('data.0.id', $cuentaPrimero->id)
            ->assertJsonPath('data.0.usuario_id', $primero->id)
            ->assertJsonPath('data.0.cbu', $cuentaPrimero->cbu)
            ->assertJsonPath('data.0.saldo', '1250.50')
            ->assertJsonPath('data.0.tipo', $cuentaPrimero->tipo)
            ->assertJsonPath('data.0.moneda', $cuentaPrimero->moneda)
            ->assertJsonPath('data.0.nombre_usuario', 'Ana Cuenta');

        expect($response->json('data.0'))->toHaveKeys([
            'id',
            'usuario_id',
            'cbu',
            'saldo',
            'tipo',
            'moneda',
            'nombre_usuario',
        ]);
    }

    public function test_el_listado_de_cuentas_usa_15_por_pagina_y_rechaza_mas_de_100(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        Cuenta::factory()->count(16)->create();
        $token = auth('api')->login($administrador);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/accounts')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(15, 'data');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/accounts?per_page=101')
            ->assertUnprocessable();
    }

    public function test_un_administrador_puede_obtener_una_cuenta_por_su_cbu_mediante_query(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $cuenta = Cuenta::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->getJson('/api/v1/admin/accounts?cbu='.$cuenta->cbu)
            ->assertOk()
            ->assertJsonPath('id', $cuenta->id)
            ->assertJsonPath('cbu', $cuenta->cbu)
            ->assertJsonPath('usuario_id', $cuenta->usuario_id);
    }

    public function test_buscar_una_cuenta_por_cbu_inexistente_devuelve_un_error_claro(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->getJson('/api/v1/admin/accounts?cbu=0000000000000000000000')
            ->assertNotFound()
            ->assertJsonPath('message', 'No se encontró una cuenta con el CBU indicado.');
    }

    public function test_un_administrador_puede_crear_una_cuenta_para_un_usuario_sin_cuenta(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $usuario = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->postJson('/api/v1/admin/accounts', [
                'usuario_id' => $usuario->id,
                'tipo' => 'corriente',
                'moneda' => 'USD',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('usuario_id', $usuario->id)
            ->assertJsonPath('tipo', 'corriente')
            ->assertJsonPath('moneda', 'USD')
            ->assertJsonPath('nombre_usuario', $usuario->nombre);

        $this->assertDatabaseHas('cuentas', [
            'usuario_id' => $usuario->id,
            'tipo' => 'corriente',
            'moneda' => 'USD',
        ]);
    }

    public function test_un_administrador_puede_restaurar_una_cuenta_dada_de_baja(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $usuario = User::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'ahorro',
            'moneda' => 'ARS',
        ]);
        $cuenta->delete();

        $response = $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->postJson('/api/v1/admin/accounts', [
                'usuario_id' => $usuario->id,
                'tipo' => 'corriente',
                'moneda' => 'USD',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('id', $cuenta->id)
            ->assertJsonPath('usuario_id', $usuario->id)
            ->assertJsonPath('tipo', 'corriente')
            ->assertJsonPath('moneda', 'USD');

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuenta->id,
            'usuario_id' => $usuario->id,
            'deleted_at' => null,
            'tipo' => 'corriente',
            'moneda' => 'USD',
        ]);
    }

    public function test_un_usuario_no_administrador_no_puede_crear_cuentas(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'usuario',
        ]);
        $destinatario = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($usuario))
            ->postJson('/api/v1/admin/accounts', [
                'usuario_id' => $destinatario->id,
            ])
            ->assertForbidden();
    }

    public function test_un_administrador_no_puede_crear_una_segunda_cuenta_para_el_mismo_usuario(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $usuario = User::factory()->create();
        Cuenta::factory()->create(['usuario_id' => $usuario->id]);

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->postJson('/api/v1/admin/accounts', [
                'usuario_id' => $usuario->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El usuario ya tiene una cuenta.');
    }

    public function test_actualizar_una_cuenta_no_deberia_reasignarla_a_otro_usuario(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $usuario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'ahorro',
        ]);

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->patchJson('/api/v1/admin/accounts/'.$cuenta->id, [
                'usuario_id' => $otroUsuario->id,
                'tipo' => 'corriente',
            ])
            ->assertOk()
            ->assertJsonPath('usuario_id', $usuario->id)
            ->assertJsonPath('tipo', 'corriente');

        expect($cuenta->refresh()->usuario_id)->toBe($usuario->id)
            ->and($cuenta->tipo)->toBe('corriente');
    }

    public function test_actualizar_una_cuenta_conserva_su_usuario_y_modifica_sus_datos_permitidos(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $usuario = User::factory()->create();
        $cuenta = Cuenta::factory()->create(['usuario_id' => $usuario->id]);

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->patchJson('/api/v1/admin/accounts/'.$cuenta->id, [
                'tipo' => 'corriente',
                'moneda' => 'USD',
            ])
            ->assertOk()
            ->assertJsonPath('usuario_id', $usuario->id)
            ->assertJsonPath('tipo', 'corriente')
            ->assertJsonPath('moneda', 'USD');
    }

    public function test_no_se_puede_eliminar_una_cuenta_con_saldo(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'administrador',
        ]);
        $cuenta = Cuenta::factory()->create(['saldo' => 100.00]);

        $this->withHeader('Authorization', 'Bearer '.auth('api')->login($administrador))
            ->deleteJson('/api/v1/admin/accounts/'.$cuenta->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No se puede eliminar la cuenta porque su saldo debe ser 0.');

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuenta->id,
            'deleted_at' => null,
        ]);
    }
}
