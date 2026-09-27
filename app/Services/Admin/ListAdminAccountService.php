<?php

namespace App\Services\Admin;

use App\DTO\Admin\ListAdminAccountDTO;
use App\Models\Cuenta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminAccountService
{
    public function list(ListAdminAccountDTO $listAccounts): LengthAwarePaginator
    {
        return Cuenta::query()
            ->select('cuentas.*')
            ->join('users', 'users.id', '=', 'cuentas.usuario_id')
            ->whereNull('users.deleted_at')
            ->with('usuario')
            ->orderBy('users.nombre', $listAccounts->orden)
            ->paginate($listAccounts->perPage);
    }
}
