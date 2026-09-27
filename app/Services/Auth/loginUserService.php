<?php

namespace App\Services\Auth;

use App\DTO\Auth\LoginUserDTO;
use Illuminate\Support\Facades\Auth;

class loginUserService
{
    /**
     * Intenta autenticar con las credenciales recibidas.
     * Devuelve el JWT (string) si son válidas, o null si no lo son.
     *
     * attempt() verifica el hash del password internamente, por lo que
     * la contraseña nunca se maneja en texto plano ni se expone.
     */
    public function login(LoginUserDTO $data): ?string
    {
        $token = Auth::guard('api')->attempt(array_merge(
            $data->toArray(),
            ['eliminado' => false]
        ));

        return $token === false ? null : $token;
    }
}
