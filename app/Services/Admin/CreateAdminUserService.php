<?php

namespace App\Services\Admin;

use App\DTO\Account\createAccountDTO;
use App\DTO\Admin\CreateAdminUserDTO;
use App\Models\User;
use App\Services\Account\createAccountService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateAdminUserService
{
    public function __construct(private createAccountService $createAccountService) {}

    public function create(CreateAdminUserDTO $data): User
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();

            if ($data->imagen instanceof UploadedFile) {
                $attributes['imagen'] = $data->imagen->store('profiles', 'public');
            }

            // El administrador nunca puede crear otro administrador desde este endpoint.
            $usuario = User::create(array_merge($attributes, [
                'rol' => 'usuario',
                'eliminado' => false,
            ]));

            $usuario->refresh();

            $this->createAccountService->create(new createAccountDTO(
                usuario_id: $usuario->id
            ));

            return $usuario;
        });
    }
}
