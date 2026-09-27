<?php

namespace App\Services\Auth;

use App\DTO\Account\createAccountDTO;
use App\DTO\Auth\RegisterUserDTO;
use App\Models\User;
use App\Services\Account\createAccountService;

class registerUserService
{
    // inyecta el servcio en el constructor
    public function __construct(private createAccountService $createAccountService) {}

    public function create(RegisterUserDTO $data)
    {
        $usuario = User::withTrashed()
            ->where('email', $data->email)
            ->first();

        if ($usuario !== null && ($usuario->eliminado || $usuario->trashed())) {
            $usuario->restore();
            $usuario->update(array_merge($data->toArray(), ['eliminado' => false]));
        } else {
            $usuario = User::create(array_merge(
                $data->toArray(),
                ['eliminado' => false]
            ));
        }

        $usuario->refresh(); // se usa para traer el rol por default

        $cuenta = new createAccountDTO(
            usuario_id: $usuario->id
        );

        if (! $usuario->cuenta()->exists()) {
            $this->createAccountService->create($cuenta);
        }

        return $usuario;
    }
}
