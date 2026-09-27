<?php

namespace App\Services\Favorites;

use App\DTO\Favorites\listFavoritesDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class listFavoritesService
{
    /**
     * Devuelve los CBU guardados por el usuario autenticado.
     */
    public function list(listFavoritesDTO $data): Collection
    {
        $user = Auth::guard('api')->user();

        // Un id ajeno no permite listar los favoritos de otro usuario.
        if ((int) $user->id !== $data->idUser) {
            abort(403, 'No podes consultar la lista de favoritos de otro usuario.');
        }

        $cuenta_propia = $user->cuenta;

        if (! $cuenta_propia) {
            abort(404, 'El usuario autenticado no tiene una cuenta asociada.');
        }

        // Eager loading de la cadena cuentaFavorita -> usuario para poder
        // mostrar el titular sin disparar una consulta por cada favorito.
        return $cuenta_propia->favoritos()
            ->with('cuentaFavorita.usuario')
            ->get();
    }
}
