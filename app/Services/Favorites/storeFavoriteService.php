<?php

namespace App\Services\Favorites;

use App\DTO\Favorites\storeFavoriteDTO;
use App\Models\Cuenta;
use App\Models\Favorito;
use Illuminate\Support\Facades\Auth;

class storeFavoriteService
{
    /**
     * Guarda un CBU de terceros en la lista de favoritos del usuario autenticado.
     */
    public function store(storeFavoriteDTO $data): Favorito
    {
        $user = Auth::guard('api')->user();

        // 1. Solo el propio usuario puede modificar su lista de favoritos.
        if ((int) $user->id !== $data->idUser) {
            abort(403, 'No podes modificar la lista de favoritos de otro usuario.');
        }

        // 2. La cuenta propietaria del favorito es la del usuario autenticado.
        $cuenta_propia = $user->cuenta;

        if (! $cuenta_propia) {
            abort(404, 'El usuario autenticado no tiene una cuenta asociada.');
        }

        // 3. El CBU debe corresponder a una cuenta existente.
        //    Se busca por numero de CBU, no por id interno de la cuenta.
        $cuenta_favorita = Cuenta::where('cbu', $data->cbu)->first();

        if (! $cuenta_favorita) {
            abort(404, 'No existe una cuenta con el CBU indicado.');
        }

        // 4. No puede guardarse el CBU propio.
        if ($cuenta_propia->cbu === $cuenta_favorita->cbu) {
            abort(422, 'No podes guardar tu propio CBU como favorito.');
        }

        // 5. No puede duplicarse para el mismo usuario.
        //    La tabla tiene un unique(cuenta_id, cbu_favorito), pero validamos
        //    antes para devolver un JSON claro en lugar de un error de base.
        $ya_existe = Favorito::where('cuenta_id', $cuenta_propia->id)
            ->where('cbu_favorito', $data->cbu)
            ->exists();

        if ($ya_existe) {
            abort(422, 'Ese CBU ya esta en tu lista de favoritos.');
        }

        return Favorito::create([
            'cuenta_id' => $cuenta_propia->id,
            'cbu_favorito' => $data->cbu,
        ]);
    }
}
