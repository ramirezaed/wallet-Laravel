<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // Agregamos test para verificar que un usuario autenticado puede consultar su perfil
    public function test_usuario_autenticado_puede_consultar_su_perfil(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile');

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'edad' => $user->edad,
                'imagen' => $user->imagen,
            ])
            ->assertJsonMissing([
                'password' => $user->password,
            ]);
    }

    // Agregamos test para verificar que un usuario sin token no puede consultar su perfil
    public function test_usuario_sin_token_no_puede_consultar_su_perfil(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    public function test_usuario_autenticado_puede_actualizar_su_perfil_sin_exponer_la_contrasena(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/profile', [
                'nombre' => 'Nombre actualizado',
                'email' => 'actualizado@example.com',
                'password' => 'nueva-password',
                'password_confirmation' => 'nueva-password',
                'edad' => 35,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'nombre' => 'Nombre actualizado',
                'email' => 'actualizado@example.com',
                'edad' => 35,
            ])
            ->assertJsonMissingPath('password');

        $user->refresh();

        $this->assertSame('Nombre actualizado', $user->nombre);
        $this->assertTrue(Hash::check('nueva-password', $user->password));
    }

    public function test_usuario_autenticado_puede_actualizar_su_imagen_con_multipart(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->put('/api/v1/profile', [
                'nombre' => 'Nombre con imagen',
                'imagen' => UploadedFile::fake()->image('perfil.webp')->size(1024),
            ]);

        $response->assertStatus(200);

        $storedImage = $user->refresh()->imagen;

        $this->assertNotNull($storedImage);
        Storage::disk('public')->assertExists($storedImage);
    }

    public function test_usuario_autenticado_puede_actualizar_su_imagen_con_post_y_method_put(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/profile', [
                '_method' => 'PUT',
                'nombre' => 'Nombre con imagen',
                'imagen' => UploadedFile::fake()->image('perfil.png')->size(1024),
            ]);

        $response->assertStatus(200);

        $storedImage = $user->refresh()->imagen;

        $this->assertNotNull($storedImage);
        Storage::disk('public')->assertExists($storedImage);
    }

    public function test_usuario_no_puede_actualizar_a_otro_usuario(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/profile', [
                'user_id' => $otherUser->id,
                'nombre' => 'Solo mi nombre',
            ])
            ->assertStatus(200)
            ->assertJsonPath('id', $user->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nombre' => 'Solo mi nombre',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'nombre' => $otherUser->nombre,
        ]);
    }

    public function test_update_rechaza_datos_invalidos_y_no_persiste_cambios(): void
    {
        $user = User::factory()->create([
            'edad' => 30,
            'imagen' => 'original.jpg',
        ]);
        $otherUser = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/profile', [
                'nombre' => 'No debe persistir',
                'email' => $otherUser->email,
                'edad' => 121,
                'imagen' => 'perfil.gif',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'correo electrónico'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'edad' => 30,
            'imagen' => 'original.jpg',
        ]);
    }

    public function test_usuario_autenticado_se_marca_como_eliminado_sin_borrarse(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/profile')
            ->assertNoContent();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'eliminado' => true,
        ]);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_usuario_eliminado_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'email' => 'eliminado@example.com',
            'eliminado' => true,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_registro_reactiva_usuario_eliminado_y_conserva_su_cuenta(): void
    {
        $user = User::factory()->create(['email' => 'reactivar@example.com']);
        $user->cuenta()->create([
            'cbu' => '0000000000000000000099',
            'saldo' => 100,
        ]);
        $user->update(['eliminado' => true]);
        $user->delete();

        $response = $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Usuario reactivado',
            'email' => $user->email,
            'password' => 'nueva-password',
            'password_confirmation' => 'nueva-password',
            'edad' => 30,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('nombre', 'Usuario reactivado');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'eliminado' => false,
            'nombre' => 'Usuario reactivado',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('cuentas', 1);
    }
}