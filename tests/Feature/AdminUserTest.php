<?php

use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function administradorConToken(): array
{
    $administrador = User::factory()->create([
        'rol' => 'administrador',
    ]);

    return [$administrador, auth('api')->login($administrador)];
}

function usuarioComunConToken(): array
{
    $usuario = User::factory()->create([
        'rol' => 'usuario',
    ]);

    return [$usuario, auth('api')->login($usuario)];
}

// --- Listado ---

test('un administrador puede listar usuarios', function () {
    [, $token] = administradorConToken();
    User::factory()->count(3)->create();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(4, 'data');
});

test('el listado de usuarios no expone la contraseña', function () {
    [, $token] = administradorConToken();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users');

    $response->assertStatus(200);
    expect($response->json('data.0'))->not->toHaveKey('password');
});

test('el listado de usuarios ordena por created_at segun el parametro orden', function () {
    [$administrador, $token] = administradorConToken();

    $ultimo = User::factory()->create([
        'created_at' => now()->addDay(),
    ]);

    $ascendente = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users?orden=asc');
    $descendente = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users?orden=desc');

    expect($ascendente->json('data.0.id'))->toBe($administrador->id);
    expect($descendente->json('data.0.id'))->toBe($ultimo->id);
});

test('el listado de usuarios respeta per_page', function () {
    [, $token] = administradorConToken();
    User::factory()->count(5)->create();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users?per_page=2');

    $response
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('el listado de usuarios rechaza un per_page mayor a 100', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users?per_page=101')
        ->assertStatus(422);
});

test('el listado de usuarios rechaza un orden invalido', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users?orden=cualquiera')
        ->assertStatus(422);
});

test('el listado de usuarios no incluye a los usuarios dados de baja', function () {
    [, $token] = administradorConToken();
    $eliminado = User::factory()->create();
    $eliminado->delete();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users');

    $response
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

// --- Consulta ---

test('un administrador puede consultar un usuario', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/admin/users/{$usuario->id}");

    $response
        ->assertStatus(200)
        ->assertJson([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
        ]);
    expect($response->json())->not->toHaveKey('password');
});

test('consultar un usuario inexistente devuelve 404', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/users/99999')
        ->assertStatus(404);
});

test('consultar un usuario dado de baja devuelve 404', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();
    $usuario->delete();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/admin/users/{$usuario->id}")
        ->assertStatus(404);
});

// --- Creacion ---

test('un administrador puede crear un usuario', function () {
    [, $token] = administradorConToken();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'secreto123',
            'edad' => 30,
        ]);

    $response
        ->assertStatus(201)
        ->assertJson([
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'rol' => 'usuario',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'nuevo@test.local',
        'rol' => 'usuario',
        'eliminado' => false,
    ]);
});

test('crear un usuario tambien le crea su cuenta', function () {
    [, $token] = administradorConToken();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'secreto123',
            'edad' => 30,
        ]);

    $cuenta = Cuenta::where('usuario_id', $response->json('id'))->first();

    expect($cuenta)->not->toBeNull();
    expect($cuenta->cbu)->not->toBeEmpty();
});

test('la contraseña del usuario creado se guarda hasheada', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'secreto123',
            'edad' => 30,
        ])
        ->assertStatus(201);

    $creado = User::where('email', 'nuevo@test.local')->first();

    expect($creado->getAuthPassword())->not->toBe('secreto123');
    expect(Hash::check('secreto123', $creado->getAuthPassword()))->toBeTrue();
});

test('crear un usuario ignora el rol enviado por el cliente', function () {
    [, $token] = administradorConToken();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'secreto123',
            'edad' => 30,
            'rol' => 'administrador',
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'nuevo@test.local',
        'rol' => 'usuario',
    ]);
});

test('la respuesta de creacion no expone la contraseña', function () {
    [, $token] = administradorConToken();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'secreto123',
            'edad' => 30,
        ]);

    $response->assertStatus(201);
    expect($response->json())->not->toHaveKey('password');
});

test('crear un usuario valida los campos obligatorios', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [])
        ->assertStatus(422);
});

test('crear un usuario rechaza un email ya registrado', function () {
    [, $token] = administradorConToken();
    $existente = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => $existente->email,
            'password' => 'secreto123',
            'edad' => 30,
        ])
        ->assertStatus(422);
});

test('crear un usuario rechaza una contraseña corta', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'corta',
            'edad' => 30,
        ])
        ->assertStatus(422);
});

test('crear un usuario rechaza a un menor de edad', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/admin/users', [
            'nombre' => 'Usuario Nuevo',
            'email' => 'nuevo@test.local',
            'password' => 'secreto123',
            'edad' => 15,
        ])
        ->assertStatus(422);
});

// --- Actualizacion ---

test('un administrador puede actualizar un usuario', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/users/{$usuario->id}", [
            'nombre' => 'Nombre Editado',
            'edad' => 45,
        ])
        ->assertStatus(200)
        ->assertJson([
            'id' => $usuario->id,
            'nombre' => 'Nombre Editado',
            'edad' => 45,
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $usuario->id,
        'nombre' => 'Nombre Editado',
        'edad' => 45,
    ]);
});

test('actualizar un usuario con su propio email actual no da error de unicidad', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/users/{$usuario->id}", [
            'email' => $usuario->email,
            'nombre' => 'Nombre Editado',
        ])
        ->assertStatus(200);
});

test('actualizar un usuario con el email de otro usuario devuelve 422', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();
    $otro = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/users/{$usuario->id}", [
            'email' => $otro->email,
        ])
        ->assertStatus(422)
        ->assertJson([
            'message' => 'El correo electrónico ya está en uso.',
            'status' => 422,
        ]);
});

test('actualizar la contraseña de un usuario la guarda hasheada y no la expone', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/users/{$usuario->id}", [
            'password' => 'nuevaclave123',
        ]);

    $response->assertStatus(200);
    expect($response->json())->not->toHaveKey('password');
    expect(Hash::check('nuevaclave123', $usuario->fresh()->getAuthPassword()))->toBeTrue();
});

test('actualizar un usuario no permite cambiarle el rol', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/users/{$usuario->id}", [
            'rol' => 'administrador',
        ])
        ->assertStatus(200);

    expect($usuario->fresh()->rol)->toBe('usuario');
});

test('actualizar un usuario con una edad invalida devuelve 422', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/users/{$usuario->id}", [
            'edad' => 5,
        ])
        ->assertStatus(422);
});

test('actualizar un usuario inexistente devuelve 404', function () {
    [, $token] = administradorConToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/admin/users/99999', ['nombre' => 'Fantasma'])
        ->assertStatus(404);
});

// --- Baja logica ---

test('un administrador puede dar de baja un usuario', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/admin/users/{$usuario->id}")
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Usuario eliminado correctamente',
        ]);

    $this->assertSoftDeleted('users', ['id' => $usuario->id]);
    $this->assertDatabaseHas('users', [
        'id' => $usuario->id,
        'eliminado' => true,
    ]);
});

test('dar de baja un usuario no borra su cuenta ni sus movimientos', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();
    $cuenta = Cuenta::factory()->create(['usuario_id' => $usuario->id]);
    $movimiento = Movimiento::create([
        'cuenta_id' => $cuenta->id,
        'tipo' => 'deposito',
        'monto' => 500,
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/admin/users/{$usuario->id}")
        ->assertStatus(200);

    $this->assertDatabaseHas('cuentas', ['id' => $cuenta->id]);
    $this->assertDatabaseHas('movimientos', ['id' => $movimiento->id]);
});

test('dar de baja un usuario ya dado de baja devuelve 404', function () {
    [, $token] = administradorConToken();
    $usuario = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/admin/users/{$usuario->id}")
        ->assertStatus(200);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/admin/users/{$usuario->id}")
        ->assertStatus(404);
});

// --- Proteccion por rol y por JWT ---

test('un usuario comun no puede administrar usuarios', function (string $metodo, string $ruta) {
    [, $token] = usuarioComunConToken();
    $objetivo = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->json($metodo, str_replace('{id}', (string) $objetivo->id, $ruta))
        ->assertStatus(403)
        ->assertJson([
            'message' => 'No autorizado. Se requiere rol de administrador.',
            'status' => 403,
        ]);
})->with([
    ['GET', '/api/v1/admin/users'],
    ['POST', '/api/v1/admin/users'],
    ['GET', '/api/v1/admin/users/{id}'],
    ['PUT', '/api/v1/admin/users/{id}'],
    ['PATCH', '/api/v1/admin/users/{id}'],
    ['DELETE', '/api/v1/admin/users/{id}'],
]);

test('una peticion sin token no puede administrar usuarios', function (string $metodo, string $ruta) {
    $objetivo = User::factory()->create();

    $this->json($metodo, str_replace('{id}', (string) $objetivo->id, $ruta))
        ->assertStatus(401);
})->with([
    ['GET', '/api/v1/admin/users'],
    ['POST', '/api/v1/admin/users'],
    ['GET', '/api/v1/admin/users/{id}'],
    ['PUT', '/api/v1/admin/users/{id}'],
    ['PATCH', '/api/v1/admin/users/{id}'],
    ['DELETE', '/api/v1/admin/users/{id}'],
]);
