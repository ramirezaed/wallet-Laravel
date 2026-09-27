<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un administrador puede acceder a una ruta administrativa', function () {
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);

    $token = auth('api')->login($administrador);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/admin/ping');

    $response
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Acceso administrativo autorizado',
        ]);
});

test('un usuario que no es administrador recibe una respuesta 403', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
    ]);

    $token = auth('api')->login($usuario);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/admin/ping');

    $response
        ->assertStatus(403)
        ->assertJson([
            'message' => 'No autorizado. Se requiere rol de administrador.',
            'status' => 403,
        ]);
});

test('un usuario no autenticado no puede acceder a una ruta administrativa', function () {
    $response = $this->getJson('/api/v1/admin/ping');

    $response->assertStatus(401);
});
