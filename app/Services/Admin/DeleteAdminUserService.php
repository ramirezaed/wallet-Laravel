<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\Auth\deleteUserService;

class DeleteAdminUserService
{
    public function __construct(private deleteUserService $deleteUserService) {}

    /**
     * Reutiliza el servicio de baja de Auth para no duplicar la escritura de los
     * dos flags de baja logica (eliminado y deleted_at), que deben quedar en sync
     * para que la reactivacion al re-registrarse siga funcionando.
     */
    public function delete(User $user): User
    {
        return $this->deleteUserService->delete($user);
    }
}
