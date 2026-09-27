<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentsApiTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    public function test_UsuarioRegistrado_simula_plazo_fijo_con_monto_menor_a_uno(): void
    {
        //arrange
        $user = User::factory()->create();
        $data = [
            "monto" => 0.99,
            "plazo" => 365
        ];
        $token = auth("api")->login($user);
        //act realizar la peticion
        $response = $this->withToken($token)->postJson('/api/v1/investments/fixed-term/simulate', $data);
        //assert 
        $response->assertUnprocessable();
    }
    public function test_UsuarioRegistrado_simula_plazo_fijo_con_plazo_mayor_a_365(): void
    {
        //arrange
        $user = User::factory()->create();
        $data = [
            "monto" => 1,
            "plazo" => 366
        ];
        $token = auth("api")->login($user);
        //act realizar la peticion
        $response = $this->withToken($token)->postJson('/api/v1/investments/fixed-term/simulate', $data);
        //assert 
        $response->assertUnprocessable();
    }

    public function test_Usuario_no_registrado_simula_plazo_fijo_con_monto_menor_a_uno(): void
    {
        //arrange
        $data = [
            "monto" => 0.99,
            "plazo" => 365
        ];
        //act realizar la peticion
        $response = $this->postJson('/api/v1/investments/fixed-term/simulate', $data);
        //assert 
        $response->assertUnauthorized();
    }
    public function test_Usuario_no_registrado_simula_plazo_fijo_con_plazo_mayor_a_365(): void
    {
        //arrange
        $data = [
            "monto" => 1,
            "plazo" => 366
        ];
        //act realizar la peticion
        $response = $this->postJson('/api/v1/investments/fixed-term/simulate', $data);
        //assert 
        $response->assertUnauthorized();
    }
}
