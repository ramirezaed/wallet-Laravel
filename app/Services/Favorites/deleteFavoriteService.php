<?php

namespace App\Services\Favorites;

use App\DTO\Favorites\deleteFavoriteDTO;
use App\Models\Favorito;
use Illuminate\Support\Facades\Auth;

class deleteFavoriteService
{
    /**
     * Quita un CBU de la lista de favoritos del usuario autenticado.
     * Solo elimina la relacion: la cuenta y el usuario tercero no se tocan.
     */
    public function delete(deleteFavoriteDTO $data): void
    {
        $user = Auth::guard('api')->user();

        // Un id ajeno no permite remover relaciones de otro usuario.
        if ((int) $user->id !== $data->idUser) {
            abort(403, 'No podes modificar la lista de favoritos de otro usuario.');
        }

        $cuenta_propia = $user->cuenta;

        if (! $cuenta_propia) {
            abort(404, 'El usuario autenticado no tiene una cuenta asociada.');
        }

        // Se busca el favorito por cuenta propietaria y numero de CBU.
        $favorito = Favorito::where('cuenta_id', $cuenta_propia->id)
            ->where('cbu_favorito', $data->cbu)
            ->first();

        if (! $favorito) {
            abort(404, 'Ese CBU no esta en tu lista de favoritos.');
        }

        // delete() sobre el modelo Favorito borra unicamente la fila de
        // la tabla favoritos. La cuenta tercera queda intacta.
        $favorito->delete();
    }
}
