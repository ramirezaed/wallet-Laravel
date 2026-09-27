<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class HttpSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function loginKey(string $email, string $ip = '127.0.0.1'): string
    {
        return 'login:' . Str::lower(trim($email)) . '|' . $ip;
    }

    public function test_login_bloquea_despues_de_cinco_intentos_fallidos(): void
    {
        $email = 'usuario@test.com';
        $key = $this->loginKey($email);

        RateLimiter::clear($key);

        User::factory()->create([
            'email' => $email,
            'password' => 'password123',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'incorrecta',
            ])->assertStatus(401);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'incorrecta',
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'status' => 429,
            ])
            ->assertJsonStructure([
                'message',
                'status',
                'retry_after',
                'error',
            ]);

        $this->assertTrue($response->headers->has('Retry-After'));

        RateLimiter::clear($key);
    }

    public function test_rate_limit_normaliza_el_email(): void
    {
        $email = 'usuario@test.com';
        $key = $this->loginKey($email);

        RateLimiter::clear($key);

        User::factory()->create([
            'email' => $email,
            'password' => 'password123',
        ]);

        foreach ([
            'USUARIO@TEST.COM',
            ' usuario@test.com ',
            'Usuario@Test.Com',
            'usuario@test.com',
            'USUARIO@test.com',
        ] as $emailIntento) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $emailIntento,
                'password' => 'incorrecta',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'incorrecta',
        ])->assertStatus(429);

        RateLimiter::clear($key);
    }

    public function test_login_exitoso_limpia_intentos_fallidos(): void
    {
        $email = 'usuario@test.com';
        $key = $this->loginKey($email);

        RateLimiter::clear($key);

        User::factory()->create([
            'email' => $email,
            'password' => 'password123',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'incorrecta',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->assertStatus(200);

        $this->assertSame(0, RateLimiter::attempts($key));

        RateLimiter::clear($key);
    }

    public function test_ruta_privada_sin_token_devuelve_401_json_sin_accept(): void
    {
        $response = $this->get('/api/v1/account');

        $response
            ->assertStatus(401)
            ->assertHeader('content-type', 'application/json');

        $response->assertJson([
            'message' => 'No autenticado',
            'status' => 401,
        ]);
    }

    public function test_usuario_comun_recibe_403_json_en_ruta_admin(): void
    {
        $user = User::factory()->create([
            'rol' => 'usuario',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/v1/admin/ping');

        $response
            ->assertStatus(403)
            ->assertJson([
                'status' => 403,
            ]);
    }

    public function test_ruta_api_inexistente_devuelve_404_json(): void
    {
        $response = $this->get('/api/v1/ruta-que-no-existe');

        $response
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/json')
            ->assertJson([
                'status' => 404,
            ]);
    }

    public function test_validacion_devuelve_422_json_sin_accept(): void
    {
        $response = $this->post('/api/v1/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJson([
                'status' => 422,
            ]);
    }

    public function test_error_500_no_expone_detalles_internos(): void
    {
        Route::get('/api/v1/test-error-seguridad', function () {
            throw new \RuntimeException(
                'SQLSTATE[HY000]: contraseña_secreta tabla_interna'
            );
        });

        $response = $this->get('/api/v1/test-error-seguridad');

        $response
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Error interno del servidor',
                'status' => 500,
            ]);

        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('contraseña_secreta');
        $response->assertDontSee('tabla_interna');
    }
}
