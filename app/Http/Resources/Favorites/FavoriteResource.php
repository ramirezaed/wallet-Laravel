<?php

namespace App\Http\Resources\Favorites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // El titular se obtiene siguiendo la cadena:
        // Favorito -> cuentaFavorita (por cbu) -> usuario (User)
        return [
            'id' => $this->id,
            'cbu' => $this->cbu_favorito,
            'titular' => $this->cuentaFavorita?->usuario?->nombre,
        ];
    }
}
